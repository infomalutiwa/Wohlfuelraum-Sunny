/* Einstellungen für die Terminanfrage. Dauer der Leistungen vom Studio bestätigen lassen. */
window.SUNNY_BOOKING={
  /* Adresse des Buchungs-Backends (Live-Kalender, Zahlung, Erinnerungen).
     Leer lassen = Wunschtermin wird per WhatsApp angefragt. */
  api:'',
  whatsapp:'491733098632',
  /* Direktbuchung über Firestore: freie Zeiten live, Doppelbuchungen ausgeschlossen. false = alter WhatsApp-Weg */
  direct:true,
  daysAhead:42,      /* so viele Tage im Voraus buchbar */
  minLead:120,       /* frühestens so viele Minuten ab jetzt */
  /* Optional: Push aufs Handy/Laptop über die kostenlose App ntfy (ntfy.sh). Themen-Adresse eintragen,
     z. B. 'https://ntfy.sh/sunny-termine-x7k2q9'. Es werden nur Datum, Uhrzeit und Leistung gesendet, kein Name. */
  notify:'',
  deposit:null,   /* Anzahlungsregel, z. B. {minPrice:200,minMinutes:120,percent:50}. null = keine Anzahlung */
  slotStep:30,
  /* Öffnungszeiten: Wochentag (0 = Sonntag) -> Liste von [von, bis] in Stunden. Fehlt ein Tag, ist er online nicht buchbar
     (Mittwoch und Sonntag geschlossen, Samstag nur nach Vereinbarung). */
  hours:{1:[[8,13]],2:[[8,13],[15,20]],4:[[8,13],[15,20]],5:[[10,16]]},
  /* Preise und Dauer laut Preislisten des Studios (Stand November 2026). Bei Zeitspannen ("ca. 75–90 Min.") ist die längere Dauer eingetragen.
     cat = Gruppe in der Auswahl, price:null = Preis nach Aufwand. */
  services:[
    {id: 'kos-kennen', cat: 'Kosmetik', name: 'Kennenlernbehandlung', minutes: 45, price: 60},
    {id: 'kos-klassisch', cat: 'Kosmetik', name: 'Klassische Gesichtsbehandlung', minutes: 60, price: 79},
    {id: 'kos-aqua', cat: 'Kosmetik', name: 'Aquafacial', minutes: 60, price: 99},
    {id: 'kos-antiaging', cat: 'Kosmetik', name: 'Anti-Aging Luxusbehandlung', minutes: 75, price: 109},
    {id: 'kos-sensitive', cat: 'Kosmetik', name: 'Sensitive Skin', minutes: 60, price: 79},
    {id: 'kos-clear', cat: 'Kosmetik', name: 'Clear Skin – unreine Haut', minutes: 60, price: 79},
    {id: 'kos-akne', cat: 'Kosmetik', name: 'Akne-Spezialbehandlung', minutes: 75, price: 89},
    {id: 'kos-hydrojelly', cat: 'Kosmetik', name: 'Hydrojelly Glow', minutes: 45, price: 59},
    {id: 'kos-luxury', cat: 'Kosmetik', name: 'Luxury Facial Ritual', minutes: 90, price: 139},
    {id: 'kos-teen', cat: 'Kosmetik', name: 'Teen Clean (bis 17 Jahre)', minutes: 45, price: 49},
    {id: 'kos-frucht', cat: 'Kosmetik', name: 'Fruchtsäurepeeling', minutes: 50, price: 89},
    {id: 'kos-milch', cat: 'Kosmetik', name: 'Milchsäurepeeling', minutes: 50, price: 85},
    {id: 'kos-needling', cat: 'Kosmetik', name: 'Microneedling', minutes: 60, price: 119},
    {id: 'hs-classic', cat: 'Head Spa', name: 'Head Spa Classic', minutes: 45, price: 69},
    {id: 'hs-deluxe', cat: 'Head Spa', name: 'Head Spa Deluxe', minutes: 60, price: 89},
    {id: 'hs-premium', cat: 'Head Spa', name: 'Head Spa Premium', minutes: 90, price: 119},
    {id: 'hs-kids', cat: 'Head Spa', name: 'Kids Head Spa (6–11 Jahre)', minutes: 30, price: 49},
    {id: 'hs-teen', cat: 'Head Spa', name: 'Teen Head Spa (12–17 Jahre)', minutes: 45, price: 59},
    {id: 'mani', cat: 'Nägel', name: 'Klassische Maniküre', minutes: 40, price: 32},
    {id: 'mani-lack', cat: 'Nägel', name: 'Maniküre mit Nagellack', minutes: 50, price: 37},
    {id: 'mani-shellac', cat: 'Nägel', name: 'Maniküre mit CND Shellac', minutes: 60, price: 45},
    {id: 'gel-neu', cat: 'Nägel', name: 'Gel-Neumodellage', minutes: 90, price: 60},
    {id: 'gel-verst', cat: 'Nägel', name: 'Gel-Naturnagelverstärkung', minutes: 90, price: 55},
    {id: 'gel-auf', cat: 'Nägel', name: 'Gel-Nägel auffüllen', minutes: 90, price: 50},
    {id: 'mani-shellac-ab', cat: 'Nägel', name: 'CND Shellac entfernen', minutes: 20, price: 15},
    {id: 'gel-ab', cat: 'Nägel', name: 'Gel entfernen inklusive Nagelpflege', minutes: 30, price: 25},
    {id: 'nagel-rep', cat: 'Nägel', name: 'Nagelreparatur pro Nagel', minutes: 15, price: 7},
    {id: 'nagel-french', cat: 'Nägel', name: 'French / Babyboomer', minutes: 15, price: 10},
    {id: 'nagel-design', cat: 'Nägel', name: 'Einfaches Nageldesign', minutes: 20, price: null},
    {id: 'pedi', cat: 'Fußpflege', name: 'Klassische Pediküre', minutes: 45, price: 45},
    {id: 'pedi-lack', cat: 'Fußpflege', name: 'Pediküre mit Nagellack', minutes: 55, price: 49},
    {id: 'pedi-shellac', cat: 'Fußpflege', name: 'Pediküre mit CND Shellac', minutes: 60, price: 57},
    {id: 'fuss-med', cat: 'Fußpflege', name: 'Med. Fußpflege', minutes: 60, price: 60},
    {id: 'pedi-shellac-ab', cat: 'Fußpflege', name: 'CND Shellac entfernen', minutes: 20, price: 15},
    {id: 'fuss-peeling', cat: 'Fußpflege', name: 'Fußpeeling', minutes: 10, price: 10},
    {id: 'fuss-maske', cat: 'Fußpflege', name: 'Pflegende Fußmaske', minutes: 15, price: 12},
    {id: 'pedi-french', cat: 'Fußpflege', name: 'French mit CND Shellac', minutes: 15, price: 10},
    {id: 'lash-lift', cat: 'Wimpern & Augenbrauen', name: 'Klassisches Wimpernlifting', minutes: 60, price: 59},
    {id: 'lash-lift-kr', cat: 'Wimpern & Augenbrauen', name: 'Koreanisches Lashlifting', minutes: 75, price: 69},
    {id: 'brow-lift', cat: 'Wimpern & Augenbrauen', name: 'Augenbrauenlifting', minutes: 60, price: 59},
    {id: 'brow-henna', cat: 'Wimpern & Augenbrauen', name: 'Henna Brows', minutes: 20, price: 15},
    {id: 'brow-zupfen', cat: 'Wimpern & Augenbrauen', name: 'Augenbrauen zupfen', minutes: 15, price: 15},
    {id: 'lash-farbe', cat: 'Wimpern & Augenbrauen', name: 'Wimpern färben', minutes: 20, price: 15},
    {id: 'kombi-lift', cat: 'Wimpern & Augenbrauen', name: 'Wimpernlifting + Augenbrauenlifting', minutes: 120, price: 105},
    {id: 'kombi-lift-kr', cat: 'Wimpern & Augenbrauen', name: 'Koreanisches Lashlifting + Augenbrauenlifting', minutes: 135, price: 115},
    {id: 'kombi-henna', cat: 'Wimpern & Augenbrauen', name: 'Augenbrauen zupfen + Henna Brows', minutes: 30, price: 25},
    {id: 'kombi-farbe', cat: 'Wimpern & Augenbrauen', name: 'Wimpern färben + Augenbrauen zupfen', minutes: 35, price: 25},
    {id: 'ext-11', cat: 'Wimpern & Augenbrauen', name: '1:1-Technik – Neuset', minutes: 120, price: 89},
    {id: 'ext-soft', cat: 'Wimpern & Augenbrauen', name: 'Soft Volume 2D–3D – Neuset', minutes: 150, price: 109},
    {id: 'ext-mega', cat: 'Wimpern & Augenbrauen', name: 'Mega Volume 4D–6D – Neuset', minutes: 180, price: 129},
    {id: 'ext-11-auf2', cat: 'Wimpern & Augenbrauen', name: '1:1-Technik auffüllen (2 Wochen)', minutes: 60, price: 40},
    {id: 'ext-11-auf3', cat: 'Wimpern & Augenbrauen', name: '1:1-Technik auffüllen (3 Wochen)', minutes: 75, price: 50},
    {id: 'ext-11-auf4', cat: 'Wimpern & Augenbrauen', name: '1:1-Technik auffüllen (4 Wochen)', minutes: 90, price: 60},
    {id: 'ext-soft-auf2', cat: 'Wimpern & Augenbrauen', name: 'Soft Volume auffüllen (2 Wochen)', minutes: 75, price: 50},
    {id: 'ext-soft-auf3', cat: 'Wimpern & Augenbrauen', name: 'Soft Volume auffüllen (3 Wochen)', minutes: 90, price: 60},
    {id: 'ext-soft-auf4', cat: 'Wimpern & Augenbrauen', name: 'Soft Volume auffüllen (4 Wochen)', minutes: 105, price: 70},
    {id: 'ext-mega-auf2', cat: 'Wimpern & Augenbrauen', name: 'Mega Volume auffüllen (2 Wochen)', minutes: 90, price: 60},
    {id: 'ext-mega-auf3', cat: 'Wimpern & Augenbrauen', name: 'Mega Volume auffüllen (3 Wochen)', minutes: 105, price: 70},
    {id: 'ext-mega-auf4', cat: 'Wimpern & Augenbrauen', name: 'Mega Volume auffüllen (4 Wochen)', minutes: 120, price: 80},
    {id: 'ext-ab', cat: 'Wimpern & Augenbrauen', name: 'Wimpernverlängerung entfernen', minutes: 30, price: 25}
  ]
};
