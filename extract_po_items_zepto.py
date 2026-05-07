"""
Purchase Order Full Extractor – Zepto Format
Extracts PO header fields + items from Zepto PO PDFs.

Usage:
    python extract_po_items_zepto.py <pdf_path>           # pretty output
    python extract_po_items_zepto.py <pdf_path> --json    # JSON for PHP
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
        "%Y-%m-%d",    # 2026-04-29
        "%b %d, %Y",   # Apr 20, 2026
        "%d/%m/%Y",    # 20/04/2026
        "%d-%m-%Y",    # 20-04-2026
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

    DATE_PAT = (
        r'('
        r'\d{4}-\d{2}-\d{2}'
        r'|\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'
        r'|[A-Za-z]+\s+\d{1,2},?\s*\d{4}'
        r')'
    )

    # --------------------------------------------------
    # PO Number
    # --------------------------------------------------
    m = re.search(
        r'PO\s*No\s*[:\-]\s*([A-Z0-9\-]+)',
        text,
        re.IGNORECASE
    )

    if m:
        fields["po_number"] = m.group(1).strip()

    # --------------------------------------------------
    # PO Date
    # --------------------------------------------------
    m = re.search(
        r'PO\s*Date\s*[:\-]\s*' + DATE_PAT,
        text,
        re.IGNORECASE
    )

    if m:
        fields["po_date"] = parse_date(m.group(1).strip())

    # --------------------------------------------------
    # Release Date
    # Zepto label: "PO Release Date"
    # --------------------------------------------------
    m = re.search(
        r'(?:PO\s*)?Release\s*Date\s*[:\-]\s*' + DATE_PAT,
        text,
        re.IGNORECASE
    )

    if m:
        fields["release_date"] = parse_date(m.group(1).strip())

    # --------------------------------------------------
    # Expected Delivery Date
    # Example:
    # Expected Delivery Date: 2026-04-29
    # --------------------------------------------------
    m = re.search(
        r'Expected\s*Delivery\s*Date\s*[:\-]\s*' + DATE_PAT,
        text,
        re.IGNORECASE
    )

    if m:
        fields["buyer_expected_date"] = parse_date(m.group(1).strip())

    # --------------------------------------------------
    # Expiry Date
    # Zepto label: "PO Expiry Date"
    # --------------------------------------------------
    m = re.search(
        r'(?:PO\s*)?Expiry\s*Date\s*[:\-]\s*' + DATE_PAT,
        text,
        re.IGNORECASE
    )

    if m:
        fields["expiry_date"] = parse_date(m.group(1).strip())

    # --------------------------------------------------
    # Factory / Vendor Name
    # Zepto uses:
    # Name: XYZ COMPANY
    # --------------------------------------------------
    m = re.search(
        r'(?:Vendor\s+Details[^\n]*\n[^\n]*\n|^)'
        r'Name\s*[:\-]\s*(.+)',
        text,
        re.IGNORECASE | re.MULTILINE
    )

    if m:

        name = clean_text(m.group(1))

        # Remove merged PO fields
        name = re.sub(
            r'\s*PO\s*(No|Date|Release|Expiry).*',
            '',
            name,
            flags=re.IGNORECASE
        ).strip()

        if name:
            fields["factory_name"] = name

    return fields


def extract_po_items(pdf_path):

    results = []
    header = {}

    with pdfplumber.open(pdf_path) as pdf:

        for page_num, page in enumerate(pdf.pages, 1):

            text = page.extract_text() or ""

            # --------------------------------------------------
            # Extract Header From First Page
            # --------------------------------------------------
            if page_num == 1:
                header = extract_po_header(text)

            # --------------------------------------------------
            # Extract Tables
            # --------------------------------------------------
            tables = page.extract_tables()

            for table in tables:

                if not table:
                    continue

                # ----------------------------------------------------------
                # Detect header rows
                #
                # Zepto has a two-row header:
                # Row A:
                # Sr. | Material Code | Item Description | Quantity ...
                #
                # Row B:
                #      |               |                  | Rate | AMT
                # ----------------------------------------------------------
                header_a_idx = None

                for i, row in enumerate(table):

                    if row is None:
                        continue

                    cells_lower = [
                        clean_text(c).lower()
                        for c in row
                    ]

                    combined = ' '.join(cells_lower)

                    if (
                        'material code' in combined
                        or (
                            'item description' in combined
                            and 'quantity' in combined
                        )
                    ):
                        header_a_idx = i
                        break

                if header_a_idx is None:
                    continue

                row_a = [
                    clean_text(c)
                    for c in table[header_a_idx]
                ]

                # --------------------------------------------------
                # Check for sub-header row
                # --------------------------------------------------
                header_b = None

                if header_a_idx + 1 < len(table):

                    row_b_lower = [
                        clean_text(c).lower()
                        for c in table[header_a_idx + 1]
                    ]

                    if any(c in ('rate', 'amt') for c in row_b_lower):
                        header_b = row_b_lower

                first_data = (
                    header_a_idx + 2
                    if header_b is not None
                    else header_a_idx + 1
                )

                # --------------------------------------------------
                # Build Column Map
                # --------------------------------------------------
                col_map = {}

                for j, cell in enumerate(row_a):

                    cl = cell.lower().strip()

                    if not cl:
                        continue

                    if 'material code' in cl:
                        col_map['item_code'] = j

                    elif (
                        'item description' in cl
                        or 'description' in cl
                    ):
                        col_map['item_desc'] = j

                    elif cl in ('quantity', 'qty'):
                        col_map['qty'] = j

                if not col_map:
                    continue

                # --------------------------------------------------
                # Parse Item Rows
                # --------------------------------------------------
                for row in table[first_data:]:

                    if row is None:
                        continue

                    row_texts = [
                        clean_text(c)
                        for c in row
                    ]

                    combined = ' '.join(row_texts).lower()

                    # Skip totals / footer rows
                    if any(
                        kw in combined
                        for kw in [
                            'total',
                            'grand total',
                            'amount in words',
                            'subtotal',
                            'prepared by',
                            'verified by',
                            'authorised'
                        ]
                    ):
                        continue

                    # Skip empty rows
                    if all(t == '' for t in row_texts):
                        continue

                    # First column should be serial number
                    first_cell = (
                        clean_text(row[0])
                        if row[0]
                        else ''
                    )

                    if (
                        first_cell
                        and not re.match(r'^\d+$', first_cell.strip())
                    ):
                        continue

                    def get_col(key):

                        idx = col_map.get(key)

                        if idx is None or idx >= len(row_texts):
                            return ''

                        return row_texts[idx]

                    item_code = get_col('item_code')
                    item_desc = get_col('item_desc')
                    qty = get_col('qty')

                    # --------------------------------------------------
                    # Cleanup Description
                    # --------------------------------------------------
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

                    # --------------------------------------------------
                    # Save Row
                    # --------------------------------------------------
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
        else r"C:\Users\admin\Downloads\P4202462.pdf"
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
                f"+{'-'*(w_code+2)}+"
                f"{'-'*(w_qty+2)}+"
                f"{'-'*(w_desc+2)}+"
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