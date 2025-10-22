# GitHub Setup en Deployment Instructies

## Eerste Keer: Repository Initialiseren

### 1. Maak .gitignore aan (indien nog niet aanwezig)

Het project zou al een `.gitignore` moeten hebben, maar controleer of deze items erin staan:

```
# Configuratie bestanden met gevoelige data
config/database.php
config/email.php

# Uploads en user data
uploads/*
!uploads/.gitkeep

# Logs
logs/*
!logs/.gitkeep

# Backups
backups/*
!backups/.gitkeep

# IDE bestanden
.vscode/
.idea/
*.swp
*.swo
*~

# OS bestanden
.DS_Store
Thumbs.db
desktop.ini

# Tijdelijke bestanden
*.tmp
*.bak
*.cache
```

### 2. Initialiseer Git Repository (als nog niet gedaan)

```bash
# In de ticketportaal directory
git init
git add .
git commit -m "Initial commit - Kruit & Kramer Ticketportaal v1.0.0"
```

### 3. Koppel aan GitHub Repository

```bash
# Vervang met jouw GitHub repository URL
git remote add origin https://github.com/jouw-username/ticketportaal.git
git branch -M main
git push -u origin main
```

## Dagelijkse Workflow: Code Pushen naar GitHub

### Stap 1: Controleer Wijzigingen
```bash
git status
```

### Stap 2: Voeg Bestanden Toe
```bash
# Voeg alle gewijzigde bestanden toe
git add .

# Of specifieke bestanden
git add admin/users.php classes/User.php
```

### Stap 3: Commit met Beschrijving
```bash
git commit -m "Beschrijving van wijzigingen"

# Voorbeelden:
git commit -m "Added location functionality to user management"
git commit -m "Fixed template save button and added template parser"
git commit -m "Completed TODO-FIXES items 4-12"
```

### Stap 4: Push naar GitHub
```bash
git push origin main
```

## Op Werk PC: Project Ophalen

### Eerste Keer: Clone Repository

```bash
# Navigeer naar XAMPP htdocs directory
cd C:\xampp\htdocs

# Clone repository
git clone https://github.com/jouw-username/ticketportaal.git

# Ga naar project directory
cd ticketportaal
```

### Stap 2: Configuratie Setup

```bash
# Kopieer example configuratie bestanden
copy config\database.example.php config\database.php
copy config\email.example.php config\email.php

# Bewerk configuratie bestanden met werk PC settings
notepad config\database.php
notepad config\email.php
```

### Stap 3: Database Importeren

1. Open phpMyAdmin op werk PC
2. Maak database aan: `ticketportaal`
3. Importeer de backup: `backups\db_LAATSTEBACKUP.sql`
4. Of voer alle migraties uit zoals beschreven in DEPLOYMENT.md

### Stap 4: Bestandspermissies

```bash
# Maak directories aan
mkdir uploads
mkdir logs
mkdir backups
```

### Stap 5: Test de Applicatie

Open in browser: `http://localhost/ticketportaal/`

## Updates Ophalen van GitHub

### Op Werk PC: Pull Laatste Wijzigingen

```bash
# Navigeer naar project directory
cd C:\xampp\htdocs\ticketportaal

# Haal laatste wijzigingen op
git pull origin main
```

### Na Pull: Check voor Database Wijzigingen

Als er nieuwe migratie bestanden zijn:
1. Check `database/migrations/` directory
2. Voer nieuwe SQL bestanden uit in phpMyAdmin
3. Update je lokale database

## Backup Voordat je Pushed

### Automatische Backup Script

Voer `backup.bat` uit voordat je belangrijke wijzigingen pushed:

```bash
backup.bat
```

Dit maakt:
- Database backup in `backups/db_TIMESTAMP.sql`
- Bestanden backup in `backups/files_TIMESTAMP.tar.gz`

## Branch Strategie (Optioneel)

Voor grotere wijzigingen, gebruik branches:

```bash
# Maak nieuwe branch voor feature
git checkout -b feature/nieuwe-functionaliteit

# Werk aan feature
git add .
git commit -m "Implemented nieuwe functionaliteit"

# Push branch naar GitHub
git push origin feature/nieuwe-functionaliteit

# Merge naar main (na review)
git checkout main
git merge feature/nieuwe-functionaliteit
git push origin main
```

## Belangrijke Bestanden om NIET te Committen

Deze bestanden bevatten gevoelige informatie en mogen NOOIT naar GitHub:
- ❌ `config/database.php` (bevat database wachtwoord)
- ❌ `config/email.php` (bevat SMTP wachtwoord)
- ❌ `uploads/*` (bevat user uploads)
- ❌ `logs/*` (bevat logs met mogelijk gevoelige data)
- ❌ `backups/*` (bevat database dumps)

Deze staan in `.gitignore` en worden automatisch genegeerd.

## Deployment naar Test Server

### Via FTP/SFTP

1. **Upload bestanden:**
   - Upload alle bestanden BEHALVE: config/, uploads/, logs/, backups/
   
2. **Configuratie:**
   - Maak `config/database.php` aan op server met server credentials
   - Maak `config/email.php` aan op server met server SMTP settings
   
3. **Database:**
   - Importeer database backup via phpMyAdmin op server
   - Of voer alle migraties uit
   
4. **Permissies:**
   - Maak `uploads/`, `logs/`, `backups/` directories aan
   - Zet juiste permissies (755 voor directories, 644 voor bestanden)

### Via Git (op server)

```bash
# Op server
cd /var/www/
git clone https://github.com/jouw-username/ticketportaal.git
cd ticketportaal

# Setup configuratie
cp config/database.example.php config/database.php
nano config/database.php  # Bewerk met server settings

# Maak directories
mkdir uploads logs backups
chmod 755 uploads logs backups

# Importeer database
mysql -u user -p ticketportaal < backup.sql
```

## Snelle Referentie

### Backup Maken
```bash
backup.bat
```

### Code Pushen naar GitHub
```bash
git add .
git commit -m "Beschrijving"
git push origin main
```

### Updates Ophalen
```bash
git pull origin main
```

### Database Exporteren
```bash
C:\xampp\mysql\bin\mysqldump.exe -u root ticketportaal > backup.sql
```

### Database Importeren
```bash
C:\xampp\mysql\bin\mysql.exe -u root ticketportaal < backup.sql
```

## Support

Voor vragen over deployment:
- **Ontwikkelaar:** Damian Kessler
- **ICT Afdeling:** 777 (intern)
- **Email:** support@kruitkramer.nl
