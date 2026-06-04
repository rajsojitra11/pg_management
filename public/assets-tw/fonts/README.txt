Drop the Arial TrueType fonts here to embed the real font in the Delivery Challan PDF:

  arial.ttf        (regular)
  arial-bold.ttf   (bold — optional but recommended; the challan uses bold text)

The PDF template (Modules/DeliveryChallan/resources/views/pdf.blade.php) auto-detects
these files via @font-face. While they are absent, "Arial" falls back to DomPDF's
built-in Helvetica substitute, so the PDF still renders fine.

After adding the files: run `php artisan view:clear`, then regenerate a challan PDF.
DomPDF caches the font metrics into storage/fonts/ on the first render.

Note: Arial is a licensed font and is intentionally NOT committed to the repository.
