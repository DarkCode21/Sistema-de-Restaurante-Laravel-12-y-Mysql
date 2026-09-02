from pathlib import Path
from PIL import Image, ImageDraw, ImageFont

OUT = Path(__file__).resolve().parents[1] / 'storage' / 'app' / 'public' / 'products'
OUT.mkdir(parents=True, exist_ok=True)
FONT_BOLD = ImageFont.truetype('C:/Windows/Fonts/arialbd.ttf', 52)
FONT_SUB = ImageFont.truetype('C:/Windows/Fonts/arial.ttf', 25)
FONT_SMALL = ImageFont.truetype('C:/Windows/Fonts/arialbd.ttf', 22)

PALETTES = {
    'grill-chicken.png': ((100, 35, 12), (251, 146, 60), 'PARRILLA DE POLLO', 'Pechos, piernas y pollo entero'),
    'grill-pork.png': ((84, 20, 18), (244, 114, 94), 'PARRILLA DE CERDO', 'Costillas y brochetas al carbón'),
    'grill-beef.png': ((48, 27, 21), (245, 158, 11), 'PARRILLA DE CARNE', 'Cortes y anticuchos a la brasa'),
    'grill-combo.png': ((66, 35, 9), (251, 191, 36), 'COMBOS PARRILLEROS', 'El sabor de la brasa para compartir'),
    'grill-sides.png': ((64, 45, 14), (250, 204, 21), 'ACOMPAÑAMIENTOS', 'Papas ancochadas, arroz y extras'),
    'grill-drinks.png': ((16, 50, 72), (56, 189, 248), 'BEBIDAS FRÍAS', 'Chicha, gaseosas y refrescos'),
}


def rounded_rect(draw, box, radius, fill, outline=None, width=1):
    draw.rounded_rectangle(box, radius=radius, fill=fill, outline=outline, width=width)


def center_text(draw, y, text, font, fill):
    bbox = draw.textbbox((0, 0), text, font=font)
    draw.text(((800 - (bbox[2] - bbox[0])) / 2, y), text, font=font, fill=fill)


def draw_food(draw, filename, accent):
    # charcoal grill
    draw.ellipse((95, 260, 705, 670), fill=(20, 20, 20), outline=accent, width=8)
    for y in range(330, 570, 48):
        draw.line((175, y, 625, y), fill=(92, 92, 92), width=12)
    for x in range(210, 630, 70):
        draw.line((x, 305, x - 40, 610), fill=(62, 62, 62), width=10)

    if filename == 'grill-chicken.png':
        draw.ellipse((245, 355, 555, 530), fill=(222, 130, 38), outline=(255, 222, 145), width=7)
        draw.ellipse((485, 430, 620, 565), fill=(196, 101, 28), outline=(255, 222, 145), width=7)
        draw.line((580, 540, 670, 625), fill=(245, 226, 191), width=18)
    elif filename == 'grill-pork.png':
        for y in range(360, 560, 55):
            draw.rounded_rectangle((205, y, 610, y + 45), radius=20, fill=(188, 65, 53), outline=(255, 189, 173), width=5)
            for x in range(260, 600, 70):
                draw.line((x, y + 8, x - 20, y + 37), fill=(255, 230, 216), width=5)
    elif filename == 'grill-beef.png':
        draw.ellipse((230, 335, 590, 585), fill=(151, 55, 34), outline=(255, 177, 115), width=9)
        draw.ellipse((330, 410, 490, 535), fill=(193, 74, 48), outline=(255, 205, 147), width=7)
        draw.arc((250, 360, 565, 560), 195, 330, fill=(76, 31, 24), width=16)
    elif filename == 'grill-combo.png':
        for box, color in [((180, 370, 390, 550), (214, 115, 32)), ((410, 350, 630, 500), (154, 54, 33)), ((370, 505, 580, 610), (238, 192, 76))]:
            rounded_rect(draw, box, 34, color, (255, 226, 153), 6)
    elif filename == 'grill-sides.png':
        for i, x in enumerate(range(190, 610, 65)):
            draw.rounded_rectangle((x, 365 + (i % 2) * 30, x + 42, 570), radius=16, fill=(238, 189, 84), outline=(255, 234, 158), width=5)
        draw.ellipse((285, 430, 520, 605), fill=(246, 242, 218), outline=(255, 255, 255), width=6)
    else:
        for x, color in [(220, (121, 53, 156)), (360, (245, 185, 33)), (500, (51, 117, 189))]:
            rounded_rect(draw, (x, 320, x + 95, 610), 28, color, (210, 242, 255), 5)
            draw.rectangle((x + 10, 410, x + 85, 425), fill=(226, 247, 255))
            draw.line((x + 68, 305, x + 110, 245), fill=(210, 242, 255), width=12)


def make_asset(filename, base, accent, title, subtitle):
    image = Image.new('RGB', (800, 800), base)
    draw = ImageDraw.Draw(image)
    # diagonal warm bands
    for offset in range(-800, 1000, 70):
        draw.line((offset, 0, offset + 800, 800), fill=tuple(min(255, c + 10) for c in base), width=12)
    draw_food(draw, filename, accent)
    rounded_rect(draw, (50, 55, 750, 190), 28, (18, 24, 28), (accent[0], accent[1], accent[2]), 3)
    center_text(draw, 78, title, FONT_BOLD, (255, 247, 232))
    center_text(draw, 140, subtitle, FONT_SUB, (255, 226, 175))
    rounded_rect(draw, (250, 690, 550, 744), 20, accent)
    center_text(draw, 704, 'SABOR A LA BRASA', FONT_SMALL, (30, 26, 20))
    image.save(OUT / filename, optimize=True)


for file_name, (base, accent, title, subtitle) in PALETTES.items():
    make_asset(file_name, base, accent, title, subtitle)

print(f'Created {len(PALETTES)} demo product images in {OUT}')
