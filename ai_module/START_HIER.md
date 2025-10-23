# 🚀 START HIER - Eenvoudige Opstart Handleiding

## Wat Heb Je Nodig?

1. **Windows PC of Server** (Windows 10/11 of Server 2019/2022)
2. **Python 3.11 of hoger** - Download van https://www.python.org/downloads/
3. **MySQL/XAMPP** - Voor de database (moet al draaien voor ticketportaal)
4. **Administrator rechten** - Om software te installeren

## Snelle Start (3 Stappen)

### Stap 1: Controleer of Python werkt

Open **Command Prompt** (cmd) en typ:
```
python --version
```

Je moet iets zien zoals: `Python 3.11.x` of hoger.

**Werkt niet?** Installeer Python van https://www.python.org/downloads/ (vink "Add to PATH" aan!)

### Stap 2: Ga naar de juiste map

In Command Prompt, ga naar waar je ticketportaal staat:
```
cd C:\xampp\htdocs\ticketportaal\ai_module
```

(Pas het pad aan als je ticketportaal ergens anders staat!)

### Stap 3: Start de installatie

**Optie A - Eenvoudigste manier:**

Dubbelklik op het bestand: `install.bat`

Klik rechts → "Run as Administrator"

**Optie B - Via Command Prompt:**

Als Administrator, typ:
```
install.bat
```

## Wat Gebeurt Er Nu?

Het script gaat automatisch:
1. ✅ Python packages installeren (5-10 minuten)
2. ✅ Ollama downloaden en installeren (5 minuten)
3. ✅ Llama AI model downloaden (10-30 minuten, 4.7GB!)
4. ✅ Alles configureren

**LET OP:** Het downloaden van het AI model duurt lang! Heb geduld ☕

## Na de Installatie

### Test of het werkt:

1. **Open Command Prompt** in de ai_module map
2. Typ:
   ```
   python verify_installation.py
   ```

Dit checkt of alles goed is geïnstalleerd.

### Start de AI Service:

**Optie 1 - Als Windows Service (aanbevolen):**
```powershell
Start-Service TicketportaalRAG
```

**Optie 2 - Handmatig (voor testen):**
```
cd scripts
python rag_api.py
```

### Check of het werkt:

Open je browser en ga naar:
```
http://localhost:5005/health
```

Je moet een groene status zien! ✅

## Eerste Keer Gebruik

### 1. Sync je tickets naar AI:

```
cd scripts
python sync_tickets_to_vector_db.py --limit 10
```

Dit synchroniseert 10 tickets als test (duurt 2-3 minuten).

### 2. Bekijk het AI Dashboard:

Open in je browser:
```
http://localhost/admin/ai_dashboard.php
```

Hier zie je of alles werkt!

### 3. Test AI suggesties:

1. Open een ticket in je ticketportaal
2. Scroll naar beneden
3. Je ziet nu AI suggesties! 🤖

## Hulp Nodig?

### Probleem: "Python not found"

**Oplossing:**
1. Installeer Python van https://www.python.org/downloads/
2. **BELANGRIJK:** Vink "Add Python to PATH" aan tijdens installatie!
3. Herstart Command Prompt

### Probleem: "Access Denied" of "Permission Error"

**Oplossing:**
- Klik rechts op Command Prompt
- Kies "Run as Administrator"
- Probeer opnieuw

### Probleem: Installatie stopt of geeft errors

**Oplossing:**
1. Check of MySQL/XAMPP draait
2. Check of je internetverbinding werkt
3. Kijk in de logs: `ai_module\logs\`
4. Stuur me de error message!

### Probleem: AI suggesties verschijnen niet

**Oplossing:**
1. Check of RAG API draait: http://localhost:5005/health
2. Check `config/ai_config.php` → `AI_ENABLED` moet `true` zijn
3. Voer eerst een sync uit (zie stap 1 hierboven)

## Handige Commando's

### Service beheren:
```powershell
# Status checken
Get-Service TicketportaalRAG

# Starten
Start-Service TicketportaalRAG

# Stoppen
Stop-Service TicketportaalRAG

# Herstarten
Restart-Service TicketportaalRAG
```

### Logs bekijken:
```powershell
# Laatste 50 regels van de log
Get-Content logs\rag_api_*.log -Tail 50
```

### Sync uitvoeren:
```
cd scripts
python sync_tickets_to_vector_db.py --since-hours 24
```

## Volgende Stappen

Na succesvolle installatie:

1. ✅ Laat het systeem een nacht draaien
2. ✅ Check het AI Dashboard de volgende dag
3. ✅ Test AI suggesties op verschillende tickets
4. ✅ Geef feedback: werkt het goed? 👍👎

## Nog Steeds Problemen?

### Stuur me deze info:

1. **Welke stap lukt niet?**
2. **Wat is de error message?**
3. **Screenshot van het probleem**
4. **Output van:** `python verify_installation.py`

### Waar vind ik meer info?

- **Volledige handleiding:** `INSTALLATION_GUIDE_NL.md`
- **Logs:** `ai_module\logs\`
- **Configuratie:** `config\ai_config.php`

---

## TL;DR - Kortste Versie

```bash
# 1. Open Command Prompt als Administrator
# 2. Ga naar ai_module map
cd C:\xampp\htdocs\ticketportaal\ai_module

# 3. Run installatie
install.bat

# 4. Wacht tot het klaar is (30-60 minuten)

# 5. Test het
python verify_installation.py

# 6. Start de service
Start-Service TicketportaalRAG

# 7. Sync tickets
cd scripts
python sync_tickets_to_vector_db.py --limit 10

# 8. Check dashboard
# Open browser: http://localhost/admin/ai_dashboard.php
```

**Klaar!** 🎉

---

*Laatste update: 2024-10-23*
*Hulp nodig? Stuur me een berichtje!*
