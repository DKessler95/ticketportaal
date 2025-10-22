<?php
/**
 * Landing Page
 * 
 * Welcome page with system overview and navigation to login, register, and knowledge base
 */

require_once __DIR__ . '/includes/functions.php';

// Initialize session
initSession();

// Redirect if already logged in
if (checkLogin()) {
    redirectToDashboard();
}
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - ICT Support Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
    <style>
        .hero-section {
            background: linear-gradient(135deg, #004E89 0%, #1A7F8E 100%);
            color: white;
            padding: 100px 0;
            margin-bottom: 50px;
        }
        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #FF6B35 0%, #FF8555 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 28px;
            box-shadow: 0 4px 12px rgba(255, 107, 53, 0.3);
        }
        .feature-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
        }
        .feature-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        .navbar-brand img {
            max-height: 50px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <img src="<?php echo SITE_URL; ?>/assets/images/logo/Kruit/logo.svg" 
                     alt="Kruit & Kramer" 
                     style="max-height: 45px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="knowledge_base.php">Kennisbank</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-primary ms-2" href="login.php">Inloggen</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">
                        Welkom bij het ICT Ticketportaal
                    </h1>
                    <p class="lead mb-4">
                        Professioneel beheer van ICT support verzoeken voor Kruit & Kramer. 
                        Meld problemen, volg de voortgang en vind oplossingen in onze kennisbank.
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="login.php" class="btn btn-light btn-lg px-5">
                            <i class="bi bi-box-arrow-in-right"></i> Inloggen
                        </a>
                        <a href="knowledge_base.php" class="btn btn-outline-light btn-lg px-5">
                            <i class="bi bi-book"></i> Kennisbank
                        </a>
                    </div>
                    <div class="mt-4">
                        <p class="mb-0">
                            <i class="bi bi-info-circle"></i> 
                            <strong>Geen toegang?</strong> Neem contact op met de ICT afdeling van Kruit & Kramer
                        </p>
                        <p class="mb-0">
                            <i class="bi bi-telephone-fill"></i> Telefoon: <strong>777</strong>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="container mb-5">
        <div class="row text-center mb-5">
            <div class="col-lg-8 mx-auto">
                <h2 class="fw-bold mb-3">Hoe werkt het?</h2>
                <p class="text-muted">
                    Ons ticketsysteem maakt het eenvoudig om ICT problemen te melden en op te lossen
                </p>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <span>📝</span>
                        </div>
                        <h4 class="mb-3">Ticket aanmaken</h4>
                        <p class="text-muted">
                            Meld uw ICT probleem via het webportaal of stuur een email naar 
                            <strong>ict@kruit-en-kramer.nl</strong>. U ontvangt direct een ticketnummer.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <span>🔍</span>
                        </div>
                        <h4 class="mb-3">Voortgang volgen</h4>
                        <p class="text-muted">
                            Volg de status van uw tickets in real-time. Ontvang automatische 
                            email notificaties bij statuswijzigingen en updates.
                        </p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm feature-card">
                    <div class="card-body text-center p-4">
                        <div class="feature-icon">
                            <span>📚</span>
                        </div>
                        <h4 class="mb-3">Kennisbank</h4>
                        <p class="text-muted">
                            Zoek in onze kennisbank naar oplossingen voor veelvoorkomende problemen. 
                            Los problemen zelfstandig op zonder ticket aan te maken.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- System Features -->
    <section class="bg-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6 mb-4">
                    <h3 class="fw-bold mb-3">Belangrijkste functies</h3>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Tickets aanmaken via web of email
                        </li>
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Real-time status updates en notificaties
                        </li>
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Bestanden toevoegen aan tickets (max 10MB)
                        </li>
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Prioritering en categorisering van problemen
                        </li>
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Uitgebreide kennisbank met zoekfunctie
                        </li>
                        <li class="mb-3">
                            <span class="text-primary fw-bold">✓</span>
                            Tevredenheidsrating na oplossing
                        </li>
                    </ul>
                </div>

                <div class="col-lg-6 mb-4">
                    <h3 class="fw-bold mb-3">Voor wie?</h3>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h5 class="card-title">👤 Medewerkers</h5>
                            <p class="card-text mb-0">
                                Meld ICT problemen, volg tickets en zoek oplossingen in de kennisbank
                            </p>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-body">
                            <h5 class="card-title">🛠️ ICT Agents</h5>
                            <p class="card-text mb-0">
                                Beheer toegewezen tickets, update statussen en communiceer met gebruikers
                            </p>
                        </div>
                    </div>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body">
                            <h5 class="card-title">⚙️ Beheerders</h5>
                            <p class="card-text mb-0">
                                Gebruikersbeheer, rapportages, categorieën en systeem configuratie
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="container my-5 py-5">
        <div class="row">
            <div class="col-lg-8 mx-auto text-center">
                <h2 class="fw-bold mb-4">Klaar om te beginnen?</h2>
                <p class="lead text-muted mb-4">
                    Log in met uw Kruit & Kramer account en begin direct met het melden van ICT problemen
                </p>
                <div class="d-flex gap-3 justify-content-center flex-wrap">
                    <a href="login.php" class="btn btn-primary btn-lg px-5">
                        <i class="bi bi-box-arrow-in-right"></i> Inloggen
                    </a>
                    <a href="knowledge_base.php" class="btn btn-outline-primary btn-lg px-5">
                        <i class="bi bi-book"></i> Bekijk Kennisbank
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-5 mt-5" style="background: linear-gradient(135deg, #004E89 0%, #1A7F8E 100%);">
        <div class="container">
            <div class="row text-white">
                <div class="col-md-6 mb-4 mb-md-0">
                    <img src="<?php echo SITE_URL; ?>/assets/images/logo/Kruit/logo.svg" 
                         alt="Kruit & Kramer" 
                         class="mb-3" 
                         style="max-width: 180px; filter: brightness(0) invert(1);">
                    <p class="mb-2">
                        Professioneel ICT support ticketsysteem
                    </p>
                    <p class="mb-0">
                        <i class="bi bi-telephone-fill"></i> Telefoon: <strong>777</strong>
                    </p>
                </div>
                <div class="col-md-6 text-md-end">
                    <h5 class="mb-3">Snelle Links</h5>
                    <p class="mb-2">
                        <a href="knowledge_base.php" class="text-white text-decoration-none">
                            <i class="bi bi-book"></i> Kennisbank
                        </a>
                    </p>
                    <p class="mb-2">
                        <a href="login.php" class="text-white text-decoration-none">
                            <i class="bi bi-box-arrow-in-right"></i> Inloggen
                        </a>
                    </p>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center text-white">
                <small>&copy; <?php echo date('Y'); ?> Kruit & Kramer. Alle rechten voorbehouden.</small>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
