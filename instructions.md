# INSTRUCTION.md — Website Page to DOCX Generator (Core PHP with UI)

## Goal

Build a **Core PHP web application with UI** that converts website page content into **properly formatted DOCX files**.

The system must:
- Provide a **web UI** to add URLs
- Allow adding **one or multiple URLs**
- Allow specifying an **optional DIV / CSS class selector**
- If selector is provided → extract content **inside that DIV**
- If selector is NOT provided → extract **full `<body>` content**
- Preserve and **format layout correctly in DOCX**
- Add **Meta Title and Meta Description** at the top of DOCX (if available)
- Generate **one DOCX file per URL**
- Save DOCX files using **slug-based filenames**
- Use **Core PHP only** (no frameworks)

This file is the **only instruction document**.

---

## Technology Constraints

- PHP ≥ 7.4
- Composer allowed
- Library: `phpoffice/phpword`
- HTML parsing via `DOMDocument` and `DOMXPath`
- No Laravel, Yii, WordPress, or other frameworks

---

## User Interface Requirements

The application must provide **one web page UI** containing:

1. **Textarea for URLs**
   - Multiple URLs allowed
   - One URL per line

2. **Optional Input for DIV / CSS Class**
   - User enters class name **without dot**
   - Example:
     ```
     your_right_contents
     ```

3. **Submit Button**
   - Example label: `Generate DOCX`

4. **Result / Status Section**
   - Show success or failure per URL
   - Show skipped reasons (invalid URL, selector not found, fetch failed)

---

## Input Rules

### URLs
- Trim whitespace
- Allow only `http` and `https`
- Invalid URLs must be skipped
- One URL failure must not stop others

### DIV Selector
- Optional
- Applies to all URLs
- If empty → extract `<body>` content

---

## HTML Fetching Rules

- Fetch full HTML server-side
- Enforce timeout
- No JavaScript execution
- Do not execute external scripts

---

## Meta Data Extraction Rules

For each page:
- Extract `<title>` → **Meta Title**
- Extract `<meta name="description">` → **Meta Description** (if exists)

DOCX structure order must be:

1. Meta Title (formatted as main heading)
2. Meta Description (formatted as normal paragraph / italic)
3. Page content

If meta title or description does not exist:
- Skip silently

---

## Content Extraction Rules

### If CSS class selector is provided

Extract content only from the **first element** (any HTML tag) that has the given class:

```html
<div class="your_right_contents">...</div>
<section class="your_right_contents">...</section>
<article class="your_right_contents">...</article>
