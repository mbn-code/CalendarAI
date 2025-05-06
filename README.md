# Selvoptimerende Kalender

# CalendarAI - Smart Kalender System

En intelligent kalenderapplikation der hjælper med at optimere din daglige planlægning ved hjælp af AI.

## 🚀 Installation og Opsætning

### Forudsætninger
- XAMPP (med PHP 8.0+)
- HeidiSQL eller lignende MySQL værktøj
- Git (valgfrit)

### Trin-for-trin opsætning

1. **XAMPP Installation**
   - Download og installer XAMPP fra [https://www.apachefriends.org/](https://www.apachefriends.org/)
   - Start Apache og MySQL services i XAMPP Control Panel

2. **Projekt Installation**
   - Naviger til `c:\xampp\htdocs\`
   - Opret en mappe kaldet `CalendarAI`
   - Kopier alle projektfiler til denne mappe

3. **Database Opsætning**
   - Åbn HeidiSQL
   - Opret forbindelse til localhost (standard credentials):
     - Host: localhost
     - Bruger: root
     - Password: her burde der sættes en .env fil op med kodeord lige nu er standard ingen kodeord (standard i projektet - kan ændres i db.php)
   - Opret en ny database kaldet `calendar`
   - Importér `database.sql` filen fra projektmappen

4. **Konfiguration**
   - Åbn `backend/db.php`
   - Verificer at database credentials matcher din opsætning
   - Juster evt. andre indstillinger i `config.php`

## 🗂️ Vigtige Filer og Mapper

### Backend
- `backend/db.php`: Database forbindelse og konfiguration
- `backend/ScheduleOptimizer.php`: Kernen i AI-optimeringen
- `api/optimize.php`: API endpoint for skemaoptimering

### Frontend
- `index.html`: Hovedsiden
- `js/calendar.js`: Kalender funktionalitet
- `css/styles.css`: Styling

### Konfiguration
- `config.php`: Globale konfigurationsindstillinger
- `database.sql`: Database struktur og initial data

## 👤 Standard Login

Efter installation kan du logge ind med følgende credentials:
- Brugernavn: admin
- Password: password

## 🔧 Fejlfinding

### Almindelige Problemer

1. **Database Forbindelsesfejl**
   - Verificer at MySQL kører i XAMPP
   - Tjek database credentials i `backend/db.php`
   - Sikr at databasen `calendar` eksisterer

2. **Blank Side / 500 Fejl**
   - Tjek PHP error log i `logs/debug.log`
   - Verificer at alle PHP filer har korrekte rettigheder
   - Sikr at required PHP extensions er aktiveret i XAMPP

3. **API Fejl**
   - Tjek browser console for fejlmeddelelser
   - Verificer at Apache kører
   - Tjek API responses i Network tab i browser developer tools

## 💡 Brug af Systemet

1. **Kalender Visning**
   - Daglig, ugentlig og månedlig visning

2. **AI Optimering**
   - Vælg dage der skal optimeres
   - Vælg optimeringstype (standard, travl uge, konflikter)
   - Gennemse og godkend foreslåede ændringer

3. **Brugerindstillinger**
   - Indstil arbejdstider
   - Konfigurer pause-præferencer
   - Tilpas optimeringsparametre

## 📝 Dokumentation

For mere detaljeret teknisk dokumentation, se `DOCS.md` i projektmappen.

## 🔐 Sikkerhed

Husk at ændre standard login credentials og database passwords før deployment til produktion.

