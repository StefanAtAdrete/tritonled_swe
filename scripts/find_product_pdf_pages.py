#!/usr/bin/env python3
import argparse
import json
import re
import subprocess
from pathlib import Path


def run(cmd):
    p = subprocess.run(cmd, capture_output=True, text=True)
    if p.returncode != 0:
        raise RuntimeError(f"Command failed: {' '.join(cmd)}\n{p.stderr}")
    return p.stdout


def extract_pages_text(pdf_path: Path):
    txt = run(["pdftotext", "-layout", str(pdf_path), "-"])
    # pdftotext separates pages with form feed
    pages = txt.split("\f")
    # trailing split can be empty
    if pages and pages[-1].strip() == "":
        pages = pages[:-1]
    return pages


def score_page(text: str, keywords):
    t = text.lower()
    score = 0
    hits = []
    counts = {}
    for kw, weight in keywords:
        c = t.count(kw.lower())
        counts[kw] = c
        if c > 0:
            score += c * weight
            hits.append({"keyword": kw, "count": c, "weight": weight})
    return score, hits, counts


def contiguous_blocks(nums):
    if not nums:
        return []
    nums = sorted(nums)
    blocks = []
    start = prev = nums[0]
    for n in nums[1:]:
        if n == prev + 1:
            prev = n
        else:
            blocks.append((start, prev))
            start = prev = n
    blocks.append((start, prev))
    return blocks


def extract_page_range(pdf_path: Path, start: int, end: int, out_pdf: Path):
    tmp_dir = out_pdf.parent / ".tmp_pages"
    tmp_dir.mkdir(parents=True, exist_ok=True)
    pattern = str(tmp_dir / "page-%04d.pdf")
    run(["pdfseparate", str(pdf_path), pattern])
    page_files = [str(tmp_dir / f"page-{i:04d}.pdf") for i in range(start, end + 1)]
    run(["pdfunite", *page_files, str(out_pdf)])


def main():
    ap = argparse.ArgumentParser(description="Find product page range(s) in a PDF by weighted keyword scoring")
    ap.add_argument("pdf", help="Path to source PDF")
    ap.add_argument("--keyword", action="append", default=[], help="Keyword phrase (repeatable)")
    ap.add_argument("--weighted", action="append", default=[], help="Weighted keyword as phrase:weight, e.g. WL186:10")
    ap.add_argument("--threshold", type=int, default=5, help="Minimum page score to count as hit")
    ap.add_argument("--min-primary-count", type=int, default=1, help="Require primary keyword minimum occurrences on a hit page")
    ap.add_argument("--context", type=int, default=0, help="Optional pages of context around each block")
    ap.add_argument("--out-dir", default="docs/produkter/extracted", help="Output directory for report and extracted PDFs")
    ap.add_argument("--label", default="product", help="Output label, e.g. WL186")
    args = ap.parse_args()

    pdf_path = Path(args.pdf)
    out_dir = Path(args.out_dir) / args.label
    out_dir.mkdir(parents=True, exist_ok=True)

    keywords = []
    for w in args.weighted:
        if ":" in w:
            phrase, weight = w.rsplit(":", 1)
            keywords.append((phrase.strip(), int(weight)))
    for k in args.keyword:
        keywords.append((k.strip(), 3))

    if not keywords:
        raise SystemExit("Add at least one --keyword or --weighted")

    pages = extract_pages_text(pdf_path)
    results = []
    primary_keyword = keywords[0][0]

    for idx, text in enumerate(pages, start=1):
        score, hits, counts = score_page(text, keywords)
        if score >= args.threshold and counts.get(primary_keyword, 0) >= args.min_primary_count:
            results.append({"page": idx, "score": score, "hits": hits})

    hit_pages = [r["page"] for r in results]
    blocks = contiguous_blocks(hit_pages)

    blocks_with_context = []
    for s, e in blocks:
        s2 = max(1, s - args.context)
        e2 = min(len(pages), e + args.context)
        blocks_with_context.append((s2, e2))

    report = {
        "pdf": str(pdf_path),
        "total_pages": len(pages),
        "keywords": [{"phrase": k, "weight": w} for k, w in keywords],
        "threshold": args.threshold,
        "hits": results,
        "blocks": [{"start": s, "end": e} for s, e in blocks],
        "blocks_with_context": [{"start": s, "end": e} for s, e in blocks_with_context],
    }

    report_path = out_dir / f"{args.label}_page_hits.json"
    report_path.write_text(json.dumps(report, ensure_ascii=False, indent=2), encoding="utf-8")

    # Export each block as separate PDF
    exported = []
    for i, (s, e) in enumerate(blocks_with_context, start=1):
        out_pdf = out_dir / f"{args.label}_block{i}_{s}-{e}.pdf"
        extract_page_range(pdf_path, s, e, out_pdf)
        exported.append(str(out_pdf))

    print(f"Total pages: {len(pages)}")
    print(f"Hit pages: {hit_pages}")
    print(f"Blocks: {blocks}")
    print(f"Blocks with context: {blocks_with_context}")
    print(f"Report: {report_path}")
    if exported:
        print("Exported:")
        for p in exported:
            print(p)
    else:
        print("No blocks exported (no hits above threshold)")


if __name__ == "__main__":
    main()
