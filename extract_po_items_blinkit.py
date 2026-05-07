"""
Purchase Order Extractor — Moonstone / Blinkit Format
Columns: # | Item Code | HSN Code | Product UPC | Product Description |
         Basic Cost Price | CGST% | SGST% | CESS% | ADDT.CESS |
         Tax Amt | Landing Rate | Qty. | MRP | Margin% | Total Amt

Header fields extracted from page text:
  P.O. Number, Date (po_date + release_date), PO delivery date (buyer_expected_date),
  PO expiry date (expiry_date), Vendor name (factory_name)

Usage:
    python extract_po_items_blinkit.py <pdf_path>          # pretty output
    python extract_po_items_blinkit.py <pdf_path> --json   # JSON for PHP
"""

import sys
import re
import json
import pdfplumber
from datetime import datetime


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def clean(cell):
    """Strip whitespace and collapse internal newlines/spaces."""
    if cell is None:
        return ""
    return re.sub(r'\s+', ' ', str(cell)).strip()


def parse_date(raw):
    """
    Convert various date strings to YYYY-MM-DD for HTML <input type="date">.
    Handles: "April 23, 2026, 12:29 p.m." / "Apr 23, 2026" / "23/04/2026" etc.
    """
    if not raw:
        return ""
    # Strip trailing time component  e.g. ", 12:29 p.m."
    raw = re.sub(r',?\s*\d{1,2}:\d{2}\s*(a\.m\.|p\.m\.|AM|PM)?$', '', raw.strip(), flags=re.IGNORECASE).strip()
    for fmt in ("%B %d, %Y", "%b %d, %Y", "%d/%m/%Y", "%d-%m-%Y",
                "%Y-%m-%d", "%d %b %Y", "%d %B %Y"):
        try:
            return datetime.strptime(raw, fmt).strftime("%Y-%m-%d")
        except ValueError:
            continue
    return ""


# ---------------------------------------------------------------------------
# Header extraction  (regex over full-page text)
# ---------------------------------------------------------------------------

_DATE_PAT = (
    r'(?:'
    r'[A-Za-z]+\s+\d{1,2},?\s*\d{4}'      # April 23, 2026
    r'|'
    r'\d{1,2}[\/\-]\d{1,2}[\/\-]\d{4}'    # 23/04/2026
    r')'
)

# Optionally a trailing time like ", 12:29 p.m."
_TIME_SUFFIX = r'(?:,?\s*\d{1,2}:\d{2}\s*(?:a\.m\.|p\.m\.|AM|PM)?)?'

_DATE_FULL = _DATE_PAT + _TIME_SUFFIX


def _first(pattern, text, group=1, flags=re.IGNORECASE):
    m = re.search(pattern, text, flags)
    return m.group(group).strip() if m else ""


def extract_header(text):
    h = {
        "po_number":           "",
        "po_date":             "",
        "release_date":        "",   # PO issue date  ("Date :" field)
        "expiry_date":         "",   # PO expiry date
        "factory_name":        "",
        "buyer_expected_date": "",   # PO delivery date ("PO delivery date :" field)
    }

    # P.O. Number
    h["po_number"] = _first(r'P\.O\.\s*Number\s*[:\-]?\s*([A-Z0-9\-]+)', text)
    if not h["po_number"]:
        h["po_number"] = _first(r'PO\s*No\.?\s*[:\-]?\s*([A-Z0-9\-]+)', text)

    # Release date = the main PO issue date (labelled "Date :")
    # e.g. "Date :April 23, 2026, 12:29 p.m."
    raw = _first(r'\bDate\s*[:\-]\s*(' + _DATE_FULL + r')', text)
    h["release_date"] = parse_date(raw)
    h["po_date"]      = h["release_date"]   # keep po_date in sync

    # Buyer expected date = PO delivery date (labelled "PO delivery date :")
    # e.g. "PO delivery date :April 25, 2026, 11:59 p.m."
    raw = _first(r'PO\s+delivery\s*(?:date)?\s*[:\-]\s*(' + _DATE_FULL + r')', text)
    if not raw:
        raw = _first(r'Delivery\s+[Dd]ate\s*[:\-]?\s*(' + _DATE_FULL + r')', text)
    h["buyer_expected_date"] = parse_date(raw)

    # PO expiry date
    raw = _first(r'PO\s+expiry\s+date\s*[:\-]?\s*(' + _DATE_FULL + r')', text)
    if not raw:
        raw = _first(r'Expiry\s+Date\s*[:\-]?\s*(' + _DATE_FULL + r')', text)
    h["expiry_date"] = parse_date(raw)

    # Vendor / factory name
    raw = _first(r'Vendor\s*[:\-]\s*([A-Z][A-Z0-9 &()\.\,]+?)(?:\s+PAN|\s+P\.O\.|\n|$)', text)
    if raw and len(raw) > 3:
        h["factory_name"] = raw.strip()

    # Fallback: multi-line "Vendor Name:"
    if not h["factory_name"]:
        lines = text.splitlines()
        for i, line in enumerate(lines):
            if re.search(r'Vendor\s*(Name|:)', line, re.IGNORECASE):
                after = re.sub(r'Vendor\s*(Name\s*)?[:\-]?\s*', '', line, flags=re.IGNORECASE).strip()
                after = re.sub(r'\s*(PAN|P\.O\.).*', '', after, flags=re.IGNORECASE).strip()
                if after and len(after) > 3:
                    h["factory_name"] = after
                    break
                for j in range(i + 1, min(i + 4, len(lines))):
                    candidate = lines[j].strip()
                    candidate = re.sub(r'\s*(PAN|P\.O\.).*', '', candidate, flags=re.IGNORECASE).strip()
                    if candidate and len(candidate) > 3:
                        h["factory_name"] = candidate
                        break
                break

    return h


# ---------------------------------------------------------------------------
# Column indices for this PO format
# ---------------------------------------------------------------------------
#  0    1           2         3            4                    5
#  #  | Item Code | HSN Code | Product UPC | Product Description | Basic Cost |
#  6      7      8       9          10        11            12     13    14     15
#  CGST% | SGST% | CESS% | ADDT.CESS | Tax Amt | Landing Rate | Qty. | MRP | Margin% | Total Amt

COL_ITEM_CODE = 1
COL_ITEM_DESC = 4
COL_QTY       = 12


def _is_header_row(row):
    """Return True if this row looks like the column-header row."""
    texts = [clean(c).lower() for c in (row or [])]
    joined = ' '.join(texts)
    return ('item' in joined and 'code' in joined and
            ('qty' in joined or 'quantity' in joined) and
            'desc' in joined)


def extract_items(pdf_path):
    header = {}
    items  = []
    in_items = False

    with pdfplumber.open(pdf_path) as pdf:
        for page_num, page in enumerate(pdf.pages, 1):
            text = page.extract_text() or ""

            if page_num == 1:
                header = extract_header(text)

            tables = page.extract_tables()
            for table in tables:
                if not table:
                    continue

                for row in table:
                    if row is None:
                        continue

                    if _is_header_row(row):
                        in_items = True
                        continue

                    if not in_items:
                        continue

                    cells = [clean(c) for c in row]
                    joined_lower = ' '.join(cells).lower()

                    if any(kw in joined_lower for kw in
                           ['total quantity', 'total items', 'total amount',
                            'net amount', 'cart discount', 'terms', 'grand total',
                            'amount in words']):
                        in_items = False
                        break

                    if all(c == '' for c in cells):
                        continue

                    if not re.match(r'^\d+$', cells[0]):
                        continue

                    def get(idx):
                        return cells[idx] if idx < len(cells) else ''

                    item_code = re.sub(r'\s+', '', get(COL_ITEM_CODE))
                    item_desc = get(COL_ITEM_DESC)
                    qty       = get(COL_QTY)

                    item_desc = re.sub(r'(Colour|Color|Size|Brand)\s*:.*', '',
                                       item_desc, flags=re.IGNORECASE).strip()
                    item_desc = re.sub(r'\s+', ' ', item_desc).strip()

                    if item_code or item_desc or qty:
                        items.append({
                            'item_code': item_code,
                            'item_desc': item_desc,
                            'qty':       qty,
                        })

    return header, items


# ---------------------------------------------------------------------------
# CLI
# ---------------------------------------------------------------------------

if __name__ == "__main__":
    args      = sys.argv[1:]
    json_mode = '--json' in args
    args      = [a for a in args if a != '--json']

    pdf_path = args[0] if args else ""

    if not pdf_path:
        print("Usage: python extract_po_items_blinkit.py <pdf_path> [--json]",
              file=sys.stderr)
        sys.exit(1)

    if json_mode:
        try:
            h, items = extract_items(pdf_path)
            print(json.dumps({"header": h, "items": items}))
        except Exception as e:
            print(json.dumps({"header": {}, "items": [], "error": str(e)}))
    else:
        h, items = extract_items(pdf_path)
        print(f"\nExtracting from: {pdf_path}\n")

        print("=== HEADER ===")
        for k, v in h.items():
            print(f"  {k:20}: {v}")

        print("\n=== ITEMS ===")
        if items:
            w_code = max(len('Item Code'),   max(len(i['item_code']) for i in items))
            w_desc = max(len('Description'), max(len(i['item_desc']) for i in items))
            w_qty  = max(len('Qty'),         max(len(i['qty'])       for i in items))
            sep    = f"+{'-'*(w_code+2)}+{'-'*(w_desc+2)}+{'-'*(w_qty+2)}+"
            print(sep)
            print(f"| {'Item Code':<{w_code}} | {'Description':<{w_desc}} | {'Qty':<{w_qty}} |")
            print(sep)
            for it in items:
                print(f"| {it['item_code']:<{w_code}} | {it['item_desc']:<{w_desc}} | {it['qty']:<{w_qty}} |")
            print(sep)
        else:
            print("  (no items found)")

        print(f"\nTotal items: {len(items)}")