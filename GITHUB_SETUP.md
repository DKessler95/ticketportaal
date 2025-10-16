# GitHub Setup Instructies

## Stap 1: Git configuratie instellen

Open PowerShell in de projectmap en voer uit:

```powershell
# Vervang met jouw gegevens
git config --global user.name "Damian Kessler"
git config --global user.email "damian@kruit-en-kramer.nl"
```

## Stap 2: GitHub repository aanmaken

1. Ga naar https://github.com
2. Log in met je account
3. Klik op het "+" icoon rechtsboven
4. Kies "New repository"
5. Vul in:
   - Repository name: `ict-ticketportaal`
   - Description: "ICT Ticketportaal voor Kruit & Kramer - Ticket management systeem met email integratie"
   - Visibility: **Private** (aanbevolen voor bedrijfsapplicatie)
   - **NIET aanvinken**: "Initialize this repository with a README"
6. Klik op "Create repository"

## Stap 3: Remote toevoegen en pushen

Na het aanmaken van de repository, kopieer de HTTPS URL (bijvoorbeeld: `https://github.com/jouw-username/ict-ticketportaal.git`)

Voer dan deze commando's uit in PowerShell:

```powershell
# Navigeer naar de projectmap
cd D:\xampp\htdocs\ticketportaal

# Voeg de remote repository toe (vervang de URL met jouw repository URL)
git remote add origin https://github.com/jouw-username/ict-ticketportaal.git

# Verifieer dat de remote is toegevoegd
git remote -v

# Push de code naar GitHub
git branch -M main
git push -u origin main
```

## Stap 4: Verificatie

Na het pushen:
1. Ga naar je GitHub repository pagina
2. Ververs de pagina
3. Je zou alle bestanden moeten zien

## Belangrijke Notities

### Gevoelige Bestanden
De volgende bestanden worden NIET geüpload (staan in .gitignore):
- `config/database.php` - Bevat database wachtwoorden
- `config/email.php` - Bevat email wachtwoorden
- `uploads/*` - Geüploade bestanden
- `logs/*.log` - Log bestanden

### Vanaf Thuis Werken

Om vanaf thuis verder te werken:

```powershell
# Clone de repository
git clone https://github.com/jouw-username/ict-ticketportaal.git
cd ict-ticketportaal

# Kopieer de config templates
cp config/database.example.php config/database.php
cp config/email.example.php config/email.php

# Bewerk de config bestanden met je lokale instellingen
notepad config/database.php
notepad config/email.php

# Importeer de database
mysql -u root -p < database/schema.sql
mysql -u root -p < database/seed.sql
```

### Updates Pushen

Wanneer je wijzigingen maakt:

```powershell
# Bekijk gewijzigde bestanden
git status

# Voeg alle wijzigingen toe
git add .

# Commit met een beschrijving
git commit -m "Beschrijving van je wijzigingen"

# Push naar GitHub
git push
```

### Updates Ophalen

Wanneer je vanaf een andere locatie werkt:

```powershell
# Haal de laatste wijzigingen op
git pull
```

## Troubleshooting

### Authenticatie Problemen

Als je problemen hebt met authenticatie:

1. **Personal Access Token gebruiken** (aanbevolen):
   - Ga naar GitHub Settings → Developer settings → Personal access tokens → Tokens (classic)
   - Klik "Generate new token (classic)"
   - Geef het een naam: "Ticketportaal Development"
   - Selecteer scopes: `repo` (alle repo rechten)
   - Klik "Generate token"
   - **Kopieer de token** (je ziet hem maar één keer!)
   - Gebruik deze token als wachtwoord bij git push

2. **GitHub Desktop gebruiken**:
   - Download GitHub Desktop: https://desktop.github.com/
   - Log in met je GitHub account
   - Clone de repository via de GUI

### Merge Conflicts

Als je vanaf meerdere locaties werkt en conflicts krijgt:

```powershell
# Haal eerst de laatste versie op
git pull

# Los conflicts op in de bestanden
# Zoek naar <<<<<<< HEAD markers

# Voeg opgeloste bestanden toe
git add .

# Commit de merge
git commit -m "Merge conflicts opgelost"

# Push
git push
```

## Veiligheid

- Deel NOOIT je `config/database.php` of `config/email.php` bestanden
- Gebruik sterke wachtwoorden voor je GitHub account
- Overweeg 2FA (Two-Factor Authentication) in te schakelen op GitHub
- Maak de repository Private als het gevoelige bedrijfsinformatie bevat

## Support

Voor vragen over Git/GitHub:
- GitHub Docs: https://docs.github.com
- Git Docs: https://git-scm.com/doc
