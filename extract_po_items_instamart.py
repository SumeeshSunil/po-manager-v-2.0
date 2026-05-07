"""
Purchase Order Full Extractor
Extracts PO header fields + items from PO PDFs.

Usage:
    python extract_po_items.py <pdf_path>           # pretty output
    python extract_po_items.py <pdf_path> --json    # JSON for PHP
"""

import sys
import re
import json
import pdfplumber
from datetime import datetime


def clean_text(text):
    if text is None:
        return ""
    return re.sub(r'\s+', ' ', str(text)).strip()


def parse_date(date_str):
    """Convert various date formats to YYYY-MM-DD for HTML date input."""
    date_str = date_str.strip()

    formats = [
        "%b %d, %Y",   # Apr 20, 2026
        "%d/%m/%Y",    # 20/04/2026
        "%d-%m-%Y",    # 20-04-2026
        "%Y-%m-%d",    # 2026-04-20
        "%d %b %Y",    # 20 Apr 2026
        "%B %d, %Y",   # April 20, 2026
    ]

    for fmt in formats:
        try:
            return datetime.strptime(date_str, fmt).strftime("%Y-%m-%d")
        except ValueError:
            continue

    return ""


def extract_po_header(text):
    """Extract PO header fields from raw page text."""

    fields = {
        "po_number": "",
        "po_date": "",
        "release_date": "",
        "buyer_expected_date": "",   # NEW FIELD
        "expiry_date": "",
        "factory_name": "",
    }

    # -----------------------------------
    # PO Number
    # -----------------------------------
    m = re.search(
        r'PO\s*No\s*[:\-]\s*([A-Z0-9\-]+)',
        text,
        re.IGNORECASE
    )

    if m:
        fields["po_number"] = m.group(1).strip()

    # -----------------------------------
    # PO Date
    # -----------------------------------
    m = re.search(
        r'PO\s*Date\s*[:\-]\s*('
        r'[A-Za-z]+\s+\d{1,2},?\s*\d{4}'
        r'|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'
        r')',
        text,
        re.IGNORECASE
    )

    if m:
        fields["po_date"] = parse_date(m.group(1).strip())

    # -----------------------------------
    # Release Date
    # -----------------------------------
    m = re.search(
        r'(?:PO\s*)?Release\s*Date\s*[:\-]\s*('
        r'[A-Za-z]+\s+\d{1,2},?\s*\d{4}'
        r'|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'
        r')',
        text,
        re.IGNORECASE
    )

    if m:
        fields["release_date"] = parse_date(m.group(1).strip())

    # -----------------------------------
    # Expected Delivery Date
    # -> buyer_expected_date
    # -----------------------------------
    m = re.search(
        r'Expected\s*Delivery\s*Date\s*[:\-]\s*('
        r'[A-Za-z]+\s+\d{1,2},?\s*\d{4}'
        r'|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'
        r')',
        text,
        re.IGNORECASE
    )

    if m:
        fields["buyer_expected_date"] = parse_date(m.group(1).strip())

    # -----------------------------------
    # Expiry Date
    # -----------------------------------
    m = re.search(
        r'(?:PO\s*)?Expiry\s*Date\s*[:\-]\s*('
        r'[A-Za-z]+\s+\d{1,2},?\s*\d{4}'
        r'|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'
        r')',
        text,
        re.IGNORECASE
    )

    if m:
        fields["expiry_date"] = parse_date(m.group(1).strip())

    # -----------------------------------
    # Factory / Vendor Name
    # -----------------------------------
    m = re.search(
        r'Vendor\s*Name\s*[:\-]?\s*\n([^\n]+)',
        text,
        re.IGNORECASE
    )

    if m:
        name = m.group(1).strip()

        # Remove PO No part if present
        name = re.sub(
            r'PO\s*No.*',
            '',
            name,
            flags=re.IGNORECASE
        ).strip()

        if name:
            fields["factory_name"] = name

    # -----------------------------------
    # Fallback Vendor Name Logic
    # -----------------------------------
    if not fields["factory_name"]:

        lines = text.splitlines()

        for i, line in enumerate(lines):

            if re.search(r'Vendor\s*Name', line, re.IGNORECASE):

                for j in range(i + 1, min(i + 5, len(lines))):

                    candidate = re.sub(
                        r'PO\s*(No|Date|Release|Expiry).*',
                        '',
                        lines[j],
                        flags=re.IGNORECASE
                    ).strip()

                    if candidate and len(candidate) > 3:
                        fields["factory_name"] = candidate
                        break

                break

    return fields


def extract_po_items(pdf_path):

    results = []
    header = {}

    with pdfplumber.open(pdf_path) as pdf:

        for page_num, page in enumerate(pdf.pages, 1):

            text = page.extract_text() or ""

            # -----------------------------------
            # Extract Header From First Page
            # -----------------------------------
            if page_num == 1:
                header = extract_po_header(text)

            # -----------------------------------
            # Extract Tables
            # -----------------------------------
            tables = page.extract_tables()

            for table in tables:

                if not table:
                    continue

                header_row_idx = None
                col_map = {}

                # -----------------------------------
                # Find Header Row
                # -----------------------------------
                for i, row in enumerate(table):

                    if row is None:
                        continue

                    row_text = [
                        clean_text(c).lower()
                        for c in row
                    ]

                    has_item_code = any(
                        'item' in c and 'code' in c
                        for c in row_text
                    )

                    has_qty = any(
                        c in ('qty', 'quantity')
                        for c in row_text
                    )

                    has_desc = any(
                        'desc' in c or 'description' in c
                        for c in row_text
                    )

                    if has_item_code or (has_qty and has_desc):

                        header_row_idx = i

                        for j, cell in enumerate(row_text):

                            if 'item' in cell and 'code' in cell:
                                col_map['item_code'] = j

                            elif 'item' in cell and (
                                'desc' in cell or 'name' in cell
                            ):
                                col_map['item_desc'] = j

                            elif cell in ('qty', 'quantity'):
                                col_map['qty'] = j

                            elif 'desc' in cell and 'item' not in cell:

                                if 'item_desc' not in col_map:
                                    col_map['item_desc'] = j

                        break

                # -----------------------------------
                # Fallback Header Detection
                # -----------------------------------
                if header_row_idx is None:

                    for i, row in enumerate(table):

                        if row is None:
                            continue

                        row_text = [
                            clean_text(c).lower()
                            for c in row
                        ]

                        combined = ' '.join(row_text)

                        if (
                            'item code' in combined
                            or (
                                'qty' in combined
                                and (
                                    'desc' in combined
                                    or 'item' in combined
                                )
                            )
                        ):

                            header_row_idx = i

                            for j, cell in enumerate(row_text):

                                if 'item code' in cell:
                                    col_map['item_code'] = j

                                if (
                                    'item desc' in cell
                                    or 'description' in cell
                                ):
                                    col_map['item_desc'] = j

                                if cell.strip() in ('qty', 'quantity'):
                                    col_map['qty'] = j

                            break

                # -----------------------------------
                # Skip Invalid Tables
                # -----------------------------------
                if header_row_idx is None or not col_map:
                    continue

                # -----------------------------------
                # Extract Rows
                # -----------------------------------
                for row in table[header_row_idx + 1:]:

                    if row is None:
                        continue

                    row_texts = [clean_text(c) for c in row]

                    combined = ' '.join(row_texts).lower()

                    # Skip totals
                    if any(
                        kw in combined
                        for kw in [
                            'total',
                            'grand total',
                            'amount in words',
                            'subtotal'
                        ]
                    ):
                        continue

                    # Skip empty rows
                    if all(t == '' for t in row_texts):
                        continue

                    # First column must be numeric serial
                    first_cell = clean_text(row[0]) if row[0] else ''

                    if (
                        first_cell
                        and not re.match(r'^\d+$', first_cell.strip())
                    ):
                        continue

                    # -----------------------------------
                    # Extract Fields
                    # -----------------------------------
                    item_code = (
                        clean_text(row[col_map['item_code']])
                        if 'item_code' in col_map else ''
                    )

                    item_desc = (
                        clean_text(row[col_map['item_desc']])
                        if 'item_desc' in col_map else ''
                    )

                    qty = (
                        clean_text(row[col_map['qty']])
                        if 'qty' in col_map else ''
                    )

                    # -----------------------------------
                    # Cleanup Description
                    # -----------------------------------
                    item_desc = re.sub(
                        r'(Colour|Color|Size|Brand)\s*:.*',
                        '',
                        item_desc,
                        flags=re.IGNORECASE
                    ).strip()

                    item_desc = re.sub(
                        r'\s+',
                        ' ',
                        item_desc
                    ).strip()

                    item_desc = re.sub(
                        r'(?<=[A-Za-z]) (?=[a-z])',
                        '',
                        item_desc
                    )

                    # -----------------------------------
                    # Save Row
                    # -----------------------------------
                    if item_code or item_desc or qty:

                        results.append({
                            'item_code': item_code,
                            'item_desc': item_desc,
                            'qty': qty,
                        })

    return header, results


# =========================================================
# MAIN
# =========================================================
if __name__ == "__main__":

    args = sys.argv[1:]

    json_mode = '--json' in args

    args = [a for a in args if a != '--json']

    pdf_path = (
        args[0]
        if args
        else r"C:\Users\admin\Downloads\SOTY-1N99738266-JC2PO02359.pdf"
    )

    if json_mode:

        try:

            header, items = extract_po_items(pdf_path)

            print(json.dumps({
                "header": header,
                "items": items
            }))

        except Exception as e:

            print(json.dumps({
                "header": {},
                "items": [],
                "error": str(e)
            }))

    else:

        header, items = extract_po_items(pdf_path)

        print(f"\nExtracting from: {pdf_path}\n")

        print("=== HEADER ===")

        for k, v in header.items():
            print(f"  {k}: {v}")

        print("\n=== ITEMS ===")

        if items:

            w_code = max(
                len('Item Code'),
                max(len(i['item_code']) for i in items)
            )

            w_qty = max(
                len('Qty'),
                max(len(i['qty']) for i in items)
            )

            w_desc = max(
                len('Item Description'),
                max(len(i['item_desc']) for i in items)
            )

            sep = (
                f"+{'-' * (w_code + 2)}+"
                f"{'-' * (w_qty + 2)}+"
                f"{'-' * (w_desc + 2)}+"
            )

            print(sep)

            print(
                f"| {'Item Code':<{w_code}} "
                f"| {'Qty':<{w_qty}} "
                f"| {'Item Description':<{w_desc}} |"
            )

            print(sep)

            for item in items:

                print(
                    f"| {item['item_code']:<{w_code}} "
                    f"| {item['qty']:<{w_qty}} "
                    f"| {item['item_desc']:<{w_desc}} |"
                )

            print(sep)

        print(f"\nTotal items: {len(items)}")