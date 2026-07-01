from pathlib import Path
import re

from reportlab.lib import colors
from reportlab.lib.enums import TA_CENTER, TA_LEFT
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import ParagraphStyle, getSampleStyleSheet
from reportlab.lib.units import mm
from reportlab.platypus import (
    BaseDocTemplate, Frame, PageTemplate, PageBreak, Paragraph, Spacer,
    Table, TableStyle, KeepTogether, ListFlowable, ListItem
)

ROOT = Path(__file__).resolve().parents[1]
SOURCE = ROOT / "docs" / "visa" / "visa-admin-handbook.md"
OUTPUT = ROOT / "output" / "pdf" / "travelwheel-visa-admin-handbook.pdf"
OUTPUT.parent.mkdir(parents=True, exist_ok=True)

NAVY = colors.HexColor("#071B3B")
BLUE = colors.HexColor("#075EE8")
GREEN = colors.HexColor("#079447")
LIGHT_BLUE = colors.HexColor("#EEF4FF")
LIGHT_GRAY = colors.HexColor("#F4F7FB")
MID_GRAY = colors.HexColor("#66758C")
LINE = colors.HexColor("#DDE5F0")

styles = getSampleStyleSheet()
styles.add(ParagraphStyle(name="CoverTitle", parent=styles["Title"], fontName="Helvetica-Bold", fontSize=30, leading=35, textColor=NAVY, alignment=TA_CENTER, spaceAfter=14))
styles.add(ParagraphStyle(name="CoverSub", parent=styles["Normal"], fontSize=13, leading=20, textColor=MID_GRAY, alignment=TA_CENTER))
styles.add(ParagraphStyle(name="H1x", parent=styles["Heading1"], fontName="Helvetica-Bold", fontSize=22, leading=27, textColor=NAVY, spaceAfter=12))
styles.add(ParagraphStyle(name="H2x", parent=styles["Heading2"], fontName="Helvetica-Bold", fontSize=17, leading=22, textColor=NAVY, spaceBefore=4, spaceAfter=10))
styles.add(ParagraphStyle(name="H3x", parent=styles["Heading3"], fontName="Helvetica-Bold", fontSize=12.5, leading=16, textColor=BLUE, spaceBefore=10, spaceAfter=5))
styles.add(ParagraphStyle(name="Bodyx", parent=styles["BodyText"], fontName="Helvetica", fontSize=9.4, leading=14.2, textColor=NAVY, spaceAfter=7))
styles.add(ParagraphStyle(name="Smallx", parent=styles["BodyText"], fontSize=8, leading=11, textColor=MID_GRAY))
styles.add(ParagraphStyle(name="TOCx", parent=styles["BodyText"], fontSize=10, leading=15, textColor=NAVY, leftIndent=5))
styles.add(ParagraphStyle(name="Calloutx", parent=styles["BodyText"], fontSize=9.5, leading=14, textColor=NAVY, leftIndent=10, rightIndent=10, borderColor=BLUE, borderWidth=1, borderPadding=10, backColor=LIGHT_BLUE, spaceBefore=6, spaceAfter=10))
styles.add(ParagraphStyle(name="TableHeadx", parent=styles["BodyText"], fontName="Helvetica-Bold", fontSize=8, leading=10, textColor=colors.white))
styles.add(ParagraphStyle(name="TableBodyx", parent=styles["BodyText"], fontSize=7.6, leading=10, textColor=NAVY))


def inline(text: str) -> str:
    text = text.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")
    text = re.sub(r"`([^`]+)`", r"<font name='Courier'>\1</font>", text)
    text = re.sub(r"\*\*([^*]+)\*\*", r"<b>\1</b>", text)
    return text


class HandbookDoc(BaseDocTemplate):
    def __init__(self, filename):
        super().__init__(filename, pagesize=A4, leftMargin=18*mm, rightMargin=18*mm, topMargin=20*mm, bottomMargin=18*mm, title="TravelWheel Visa Administration Handbook", author="TravelWheel")
        frame = Frame(self.leftMargin, self.bottomMargin, self.width, self.height, id="content")
        self.addPageTemplates(PageTemplate(id="handbook", frames=frame, onPage=self.decorate))

    def decorate(self, canvas, doc):
        if doc.page == 1:
            return
        canvas.saveState()
        canvas.setStrokeColor(LINE)
        canvas.line(18*mm, A4[1]-13*mm, A4[0]-18*mm, A4[1]-13*mm)
        canvas.setFont("Helvetica-Bold", 7.5)
        canvas.setFillColor(NAVY)
        canvas.drawString(18*mm, A4[1]-10*mm, "TRAVELWHEEL VISA ADMINISTRATION")
        canvas.setFont("Helvetica", 7.5)
        canvas.setFillColor(MID_GRAY)
        canvas.drawRightString(A4[0]-18*mm, 10*mm, f"Page {doc.page}")
        canvas.restoreState()


lines = SOURCE.read_text(encoding="utf-8").splitlines()
sections = [line[3:] for line in lines if line.startswith("## ")]
story = []

story.extend([
    Spacer(1, 42*mm),
    Paragraph("TRAVELWHEEL", ParagraphStyle(name="Brand", fontName="Helvetica-Bold", fontSize=13, textColor=BLUE, alignment=TA_CENTER, tracking=3)),
    Spacer(1, 8*mm),
    Paragraph("Visa Administration Handbook", styles["CoverTitle"]),
    Paragraph("A practical guide to catalogue configuration, application operations, payments, document review, decisions, and issuance", styles["CoverSub"]),
    Spacer(1, 18*mm),
    Table([[Paragraph("VERSION", styles["Smallx"]), Paragraph("1.0", styles["Bodyx"])], [Paragraph("DATE", styles["Smallx"]), Paragraph("30 June 2026", styles["Bodyx"])], [Paragraph("AUDIENCE", styles["Smallx"]), Paragraph("Administrators, visa officers, support, and finance", styles["Bodyx"])]], colWidths=[35*mm, 90*mm], style=TableStyle([("BACKGROUND", (0,0), (-1,-1), LIGHT_GRAY), ("BOX", (0,0), (-1,-1), 0.75, LINE), ("INNERGRID", (0,0), (-1,-1), 0.5, LINE), ("VALIGN", (0,0), (-1,-1), "MIDDLE"), ("LEFTPADDING", (0,0), (-1,-1), 10), ("RIGHTPADDING", (0,0), (-1,-1), 10), ("TOPPADDING", (0,0), (-1,-1), 8), ("BOTTOMPADDING", (0,0), (-1,-1), 8)])),
    Spacer(1, 22*mm),
    Paragraph("Internal operating guide. Customer identity and travel documents must remain in private storage and be handled only through authorized screens.", styles["Calloutx"]),
    PageBreak(),
    Paragraph("Contents", styles["H1x"]),
])
for section in sections:
    story.append(Paragraph(inline(section), styles["TOCx"]))
story.append(PageBreak())

i = 0
first_h1 = True
while i < len(lines):
    line = lines[i].strip()
    if not line:
        i += 1
        continue
    if line.startswith("# "):
        if first_h1:
            first_h1 = False
        else:
            story.extend([PageBreak(), Paragraph(inline(line[2:]), styles["H1x"])])
        i += 1
        continue
    if line.startswith("## "):
        if story and not isinstance(story[-1], PageBreak):
            story.append(PageBreak())
        story.append(Paragraph(inline(line[3:]), styles["H2x"]))
        story.append(Table([["", ""]], colWidths=[18*mm, 145*mm], rowHeights=[2.2*mm], style=TableStyle([("BACKGROUND", (0,0), (0,0), GREEN), ("BACKGROUND", (1,0), (1,0), LIGHT_BLUE)])))
        story.append(Spacer(1, 4*mm))
        i += 1
        continue
    if line.startswith("#### "):
        story.append(Paragraph(inline(line[5:]), styles["H3x"]))
        i += 1
        continue
    if line.startswith("### "):
        story.append(Paragraph(inline(line[4:]), styles["H3x"]))
        i += 1
        continue
    if line.startswith("|"):
        rows = []
        while i < len(lines) and lines[i].strip().startswith("|"):
            cells = [c.strip() for c in lines[i].strip().strip("|").split("|")]
            if not all(re.fullmatch(r"[-: ]+", c) for c in cells):
                rows.append(cells)
            i += 1
        if rows:
            width = 163*mm / max(1, len(rows[0]))
            data = [[Paragraph(inline(c), styles["TableHeadx"] if r == 0 else styles["TableBodyx"]) for c in row] for r, row in enumerate(rows)]
            table = Table(data, colWidths=[width] * len(rows[0]), repeatRows=1, hAlign="LEFT")
            table.setStyle(TableStyle([("BACKGROUND", (0,0), (-1,0), NAVY), ("GRID", (0,0), (-1,-1), 0.5, LINE), ("VALIGN", (0,0), (-1,-1), "TOP"), ("ROWBACKGROUNDS", (0,1), (-1,-1), [colors.white, LIGHT_GRAY]), ("LEFTPADDING", (0,0), (-1,-1), 6), ("RIGHTPADDING", (0,0), (-1,-1), 6), ("TOPPADDING", (0,0), (-1,-1), 6), ("BOTTOMPADDING", (0,0), (-1,-1), 6)]))
            story.extend([table, Spacer(1, 4*mm)])
        continue
    if line.startswith("- "):
        items = []
        while i < len(lines) and lines[i].strip().startswith("- "):
            items.append(ListItem(Paragraph(inline(lines[i].strip()[2:]), styles["Bodyx"]), leftIndent=12))
            i += 1
        story.append(ListFlowable(items, bulletType="bullet", start="circle", leftIndent=18, bulletFontName="Helvetica", bulletFontSize=7, spaceAfter=6))
        continue
    if re.match(r"^\d+\. ", line):
        items = []
        while i < len(lines) and re.match(r"^\d+\. ", lines[i].strip()):
            items.append(ListItem(Paragraph(inline(re.sub(r"^\d+\. ", "", lines[i].strip())), styles["Bodyx"]), leftIndent=12))
            i += 1
        story.append(ListFlowable(items, bulletType="1", leftIndent=20, bulletFontName="Helvetica-Bold", bulletFontSize=8, spaceAfter=6))
        continue
    para = [line]
    i += 1
    while i < len(lines) and lines[i].strip() and not re.match(r"^(#|\-|\d+\. |\|)", lines[i].strip()):
        para.append(lines[i].strip())
        i += 1
    text = " ".join(para)
    style = styles["Calloutx"] if text.startswith("The most important rule") else styles["Bodyx"]
    story.append(Paragraph(inline(text), style))

doc = HandbookDoc(str(OUTPUT))
doc.build(story)
print(OUTPUT)
