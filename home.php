<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
if (isLoggedIn()) { redirectBasedOnRole(); }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt     = $conn->prepare("SELECT * FROM users WHERE email=? OR username=?");
    $stmt->bind_param("ss", $email, $email);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id']   = $user['id'];
        $_SESSION['name']      = $user['name'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['role']      = $user['role'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        redirectBasedOnRole();
    } else {
        $error = 'Invalid email or password.';
    }
}
$services = [
    ['bi-cpu',              'Engine Diagnostics',     'Full engine scan, fault code reading, and performance tuning for all vehicle types.'],
    ['bi-lightning-charge', 'Electrical Systems',     'Battery, alternator, wiring, ECU programming and all auto electrical repairs.'],
    ['bi-gear-wide',        'Transmission Service',   'Automatic and manual transmission repair, fluid change, and drivetrain service.'],
    ['bi-circle',           'Brake Systems',          'Brake pad replacement, rotor resurfacing, ABS diagnostics and hydraulic service.'],
    ['bi-arrows-move',      'Suspension & Alignment', 'Wheel alignment, strut replacement, steering and suspension system repairs.'],
    ['bi-thermometer-half', 'Cooling & AC',           'Radiator flush, thermostat replacement, AC recharge and climate control service.'],
];

$garage = [
    'garage_name' => 'ATECAR GARAGE',
    'address' => 'KG 17 Ave, Gishushu',
    'city' => 'Kigali',
    'district' => 'Gasabo',
    'phone' => '+250788123000',
    'email' => 'info@atecar.rw',
    'established_date' => '2010-05-15',
];

$settingsResult = $conn->query("SELECT garage_name,address,city,district,phone,email,established_date FROM garage_settings ORDER BY id ASC LIMIT 1");
if ($settingsResult && $settingsResult->num_rows > 0) {
    $garage = array_merge($garage, $settingsResult->fetch_assoc());
}

$establishedYear = !empty($garage['established_date']) ? date('Y', strtotime($garage['established_date'])) : '2010';
$experienceYears = max(1, (int)date('Y') - (int)$establishedYear);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VFDSMS | Vehicle Fault Diagnosis & Service Monitoring System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700;800&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:'Manrope',sans-serif; overflow-x:hidden; }

.top-nav { position:fixed; top:0; width:100%; z-index:1000; background:rgba(10,10,20,0.88); backdrop-filter:blur(10px); padding:14px 40px; display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid rgba(255,255,255,0.08); gap:12px; }
.top-nav .brand { color:#fff; font-size:1.4rem; font-weight:800; text-decoration:none; letter-spacing:.02em; display:flex; align-items:center; gap:10px; }
.top-nav .brand-logo { width:36px; height:36px; object-fit:contain; border-radius:50%; }
.top-nav .brand span { color:#f5a623; }
.nav-btns { display:flex; gap:10px; flex-wrap:wrap; justify-content:flex-end; }
.btn-about { background:transparent; border:2px solid #fff; color:#fff; padding:8px 24px; border-radius:30px; font-weight:600; text-decoration:none; transition:all .3s; }
.btn-about:hover { background:#fff; color:#111; }
.btn-login { background:#f5a623; border:2px solid #f5a623; color:#111; padding:8px 24px; border-radius:30px; font-weight:700; text-decoration:none; transition:all .3s; }
.btn-login:hover { background:#e09410; border-color:#e09410; color:#111; }

.hero { height:100vh; position:relative; overflow:hidden; display:flex; align-items:center; justify-content:center; }
.hero-bg { position:absolute; inset:0; background:url('https://images.unsplash.com/photo-1625047509248-ec889cbff17f?w=1600&q=80') center/cover no-repeat; }
.hero-overlay { position:absolute; inset:0; background:linear-gradient(135deg,rgba(5,5,15,0.85) 0%,rgba(10,20,40,0.72) 100%); }
.hero-content { position:relative; z-index:2; text-align:center; color:#fff; padding:0 20px; }
.hero-badge { display:inline-block; background:rgba(245,166,35,0.18); border:1px solid #f5a623; color:#f5a623; padding:6px 20px; border-radius:30px; font-size:.85rem; font-weight:600; letter-spacing:.08em; margin-bottom:20px; }
.hero-title { font-family:'Barlow Condensed',sans-serif; letter-spacing:.02em; font-size:clamp(2rem,5vw,4.1rem); font-weight:800; line-height:1.05; margin-bottom:10px; }
.hero-title span { color:#f5a623; }
.hero-acronym { font-size:clamp(.9rem,2vw,1.1rem); color:rgba(255,255,255,0.55); letter-spacing:.12em; margin-bottom:20px; }
.hero-sub { font-size:1.05rem; color:rgba(255,255,255,0.72); max-width:580px; margin:0 auto 36px; line-height:1.75; }
.hero-actions { display:flex; gap:16px; justify-content:center; flex-wrap:wrap; }
.btn-hero-primary { background:#f5a623; color:#111; padding:14px 36px; border-radius:50px; font-weight:700; font-size:1rem; text-decoration:none; transition:all .3s; display:inline-block; }
.btn-hero-primary:hover { background:#e09410; transform:translateY(-2px); box-shadow:0 8px 24px rgba(245,166,35,0.4); color:#111; }
.btn-hero-secondary { background:transparent; color:#fff; padding:14px 36px; border-radius:50px; font-weight:600; font-size:1rem; text-decoration:none; border:2px solid rgba(255,255,255,0.5); transition:all .3s; display:inline-block; }
.btn-hero-secondary:hover { border-color:#fff; background:rgba(255,255,255,0.1); color:#fff; }

.stats-bar { background:#111827; padding:28px 0; }
.stat-item .num { font-size:2rem; font-weight:800; color:#f5a623; }
.stat-item .lbl { font-size:.85rem; color:rgba(255,255,255,0.6); margin-top:4px; }

#about { padding:100px 0; background:#f8f9fa; }
.about-img-wrap { position:relative; border-radius:16px; overflow:hidden; box-shadow:0 20px 60px rgba(0,0,0,0.15); background:#fff; min-height:320px; display:flex; align-items:center; justify-content:center; }
.about-img-wrap img { width:100%; max-height:420px; object-fit:contain; display:block; }
.about-badge-float { position:absolute; bottom:24px; left:24px; background:#f5a623; color:#111; padding:12px 20px; border-radius:12px; font-weight:700; font-size:.9rem; }
.section-label { color:#f5a623; font-weight:700; font-size:.85rem; letter-spacing:.1em; text-transform:uppercase; margin-bottom:12px; }
.section-title { font-size:2.2rem; font-weight:800; color:#111827; line-height:1.25; margin-bottom:20px; }
.about-text { color:#6b7280; line-height:1.8; font-size:1rem; margin-bottom:16px; }
.feature-item { display:flex; align-items:flex-start; gap:14px; margin-bottom:20px; }
.feature-icon { width:44px; height:44px; border-radius:10px; background:rgba(245,166,35,0.12); color:#f5a623; display:flex; align-items:center; justify-content:center; font-size:1.2rem; flex-shrink:0; }
.feature-text h6 { font-weight:700; color:#111827; margin-bottom:4px; }
.feature-text p  { color:#6b7280; font-size:.9rem; margin:0; }

#services { padding:100px 0; background:#fff; }
.service-card { border-radius:16px; border:1px solid #e5e7eb; padding:32px 24px; text-align:center; transition:all .3s; height:100%; }
.service-card:hover { border-color:#f5a623; box-shadow:0 12px 40px rgba(245,166,35,0.15); transform:translateY(-4px); }
.service-icon { width:64px; height:64px; border-radius:16px; background:rgba(245,166,35,0.1); color:#f5a623; display:flex; align-items:center; justify-content:center; font-size:1.8rem; margin:0 auto 20px; }
.service-card h5 { font-weight:700; color:#111827; margin-bottom:10px; }
.service-card p  { color:#6b7280; font-size:.9rem; line-height:1.7; margin:0; }

#garage-details { padding:100px 0; background:#f8fafc; }
.detail-box { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:24px; height:100%; }
.detail-box h5 { font-family:'Barlow Condensed',sans-serif; font-size:1.5rem; margin-bottom:10px; color:#111827; }
.detail-item { display:flex; gap:10px; align-items:flex-start; margin:12px 0; color:#475569; }
.detail-item i { color:#f5a623; margin-top:3px; }
.opening-card { background:linear-gradient(135deg,#111827 0%,#1f2937 100%); color:#fff; border-radius:16px; padding:24px; height:100%; }
.opening-card .row-line { display:flex; justify-content:space-between; padding:10px 0; border-bottom:1px dashed rgba(255,255,255,0.2); }
.opening-card .row-line:last-child { border-bottom:none; }

#gallery { padding:100px 0; background:#fff; }
.gallery-card { position:relative; overflow:hidden; border-radius:16px; min-height:220px; }
.gallery-card img { width:100%; height:100%; min-height:220px; object-fit:cover; transition:transform .35s ease; }
.gallery-card:hover img { transform:scale(1.06); }
.gallery-label { position:absolute; left:12px; bottom:12px; background:rgba(17,24,39,0.8); color:#fff; border:1px solid rgba(255,255,255,0.16); padding:7px 12px; border-radius:9px; font-size:.82rem; }

.content-section { padding:90px 0; }
.content-section.alt { background:#f8fafc; }
.content-section h3 { font-family:'Barlow Condensed',sans-serif; font-size:2rem; color:#111827; margin-bottom:14px; }
.content-section p { color:#4b5563; line-height:1.8; }
.tick-list { list-style:none; padding:0; margin:0; }
.tick-list li { padding:10px 0 10px 34px; position:relative; color:#1f2937; font-weight:600; }
.tick-list li::before { content:'\2713'; position:absolute; left:0; top:8px; width:24px; height:24px; border-radius:50%; background:rgba(245,166,35,0.14); color:#f5a623; display:flex; align-items:center; justify-content:center; font-size:.9rem; font-weight:800; }

.brand-grid img,
.past-clients img { width:100%; height:130px; object-fit:cover; border-radius:12px; border:1px solid #e5e7eb; background:#fff; }
.about-banner img { width:100%; border-radius:16px; border:1px solid #e5e7eb; box-shadow:0 14px 42px rgba(15,23,42,.12); }

.sale-card,
.review-card,
.info-card { background:#fff; border:1px solid #e5e7eb; border-radius:16px; padding:24px; height:100%; }
.sale-card a { color:#111827; font-weight:700; text-decoration:none; }
.sale-card a:hover { color:#f5a623; }
.review-card .stars { color:#f5a623; letter-spacing:.15em; margin:12px 0; }
.review-card .client { font-weight:700; color:#111827; margin:0; }

.info-card h5 { font-family:'Barlow Condensed',sans-serif; font-size:1.5rem; color:#111827; margin-bottom:14px; }
.info-item { display:flex; gap:10px; align-items:flex-start; color:#475569; margin-bottom:10px; }
.info-item i { color:#f5a623; margin-top:3px; }
.quick-links a { display:inline-block; margin:0 10px 8px 0; color:#111827; text-decoration:none; font-weight:600; }
.quick-links a:hover { color:#f5a623; }

.cta-section { padding:100px 0; text-align:center; background:linear-gradient(135deg,#111827 0%,#1f2937 100%); color:#fff; }
.cta-section h2 { font-size:2.5rem; font-weight:800; margin-bottom:16px; }
.cta-section p  { color:rgba(255,255,255,0.7); font-size:1.1rem; margin-bottom:36px; }

footer { background:#0a0a14; color:rgba(255,255,255,0.5); padding:32px 0; text-align:center; font-size:.9rem; }
footer strong { color:#f5a623; }

.modal-content { border-radius:16px; border:none; overflow:hidden; }
.modal-login-header { background:linear-gradient(135deg,#111827,#1f2937); padding:32px; text-align:center; color:#fff; }
.modal-login-header .logo-icon { font-size:2.5rem; color:#f5a623; }
.modal-login-header h4 { font-weight:800; margin:8px 0 4px; }
.modal-login-header p  { color:rgba(255,255,255,0.6); font-size:.9rem; margin:0; }
.modal-body { padding:32px; }
.form-control:focus { border-color:#f5a623; box-shadow:0 0 0 3px rgba(245,166,35,0.15); }
.btn-signin { background:#f5a623; border:none; color:#111; font-weight:700; padding:12px; border-radius:10px; font-size:1rem; }
.btn-signin:hover { background:#e09410; color:#111; }

@media(max-width:768px){ .top-nav{padding:12px 20px;} .nav-btns{gap:8px;} .btn-about,.btn-login{padding:7px 14px;font-size:.85rem;} .hero-title{font-size:2rem;} .about-img-wrap img{max-height:300px;} }
</style>
</head>
<body>

<!-- Navbar -->
<nav class="top-nav">
    <a href="#" class="brand"><img src="assets/logo.png" alt="ATECAR Logo" class="brand-logo">VFD<span>SMS</span></a>
    <div class="nav-btns">
        <a href="#what-we-do" class="btn-about"><i class="bi bi-tools me-1"></i>What We Do</a>
        <a href="#gallery" class="btn-about"><i class="bi bi-images me-1"></i>Gallery</a>
        <a href="#testimonials" class="btn-about"><i class="bi bi-chat-quote me-1"></i>Reviews</a>
        <a href="#contact-blocks" class="btn-about"><i class="bi bi-envelope me-1"></i>Contact</a>
        <a href="login.php" class="btn-login">
            <i class="bi bi-box-arrow-in-right me-1"></i>Login
        </a>
    </div>
</nav>

<!-- Hero -->
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-content">
        <div class="hero-badge"><i class="bi bi-geo-alt-fill me-1"></i><?= htmlspecialchars($garage['city']) ?>, Rwanda - <?= htmlspecialchars($garage['garage_name']) ?></div>
        <h1 class="hero-title">Vehicle Fault Diagnosis &<br><span>Service Monitoring System</span></h1>
        <p class="hero-acronym">V F D S M S</p>
        <p class="hero-sub">Intelligent diagnostics, real-time service tracking, and transparent invoicing — keeping your vehicle running at its best.</p>
        <div class="hero-actions">
            <a href="#" class="btn-hero-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                <i class="bi bi-box-arrow-in-right me-2"></i>Access Your Account
            </a>
            <a href="#about" class="btn-hero-secondary">
                <i class="bi bi-info-circle me-2"></i>Learn More
            </a>
        </div>
    </div>
</section>

<!-- Stats -->
<div class="stats-bar">
    <div class="container">
        <div class="row g-3 text-center justify-content-center">
            <div class="col-12 col-md-4 stat-item"><div class="num"><?= $experienceYears ?>+</div><div class="lbl">Years Experience</div></div>
        </div>
    </div>
</div>

<!-- About -->
<section id="about">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-img-wrap">
                    <img src="assets/images (1).jpg" alt="ATECAR Garage">
                    <div class="about-badge-float"><i class="bi bi-patch-check-fill me-2"></i>Certified Garage — Since 2010</div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-label"><i class="bi bi-wrench me-1"></i>About VFDSMS</div>
                <h2 class="section-title">Vehicle Fault Diagnosis &<br>Service Monitoring System</h2>
                <p class="about-text">VFDSMS is the digital management platform of <strong><?= htmlspecialchars($garage['garage_name']) ?></strong>, located in <?= htmlspecialchars($garage['district']) ?>, <?= htmlspecialchars($garage['city']) ?>. It provides real-time vehicle diagnostics, service tracking, and invoice management for clients and staff.</p>
                <p class="about-text">Clients can log in to track their vehicle's diagnosis, view repair progress, and receive invoices — all in one place.</p>
                <div class="mt-4">
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-cpu"></i></div>
                        <div class="feature-text"><h6>Real-Time Diagnostics</h6><p>Advanced OBD scanning and fault code analysis for all vehicle makes and models.</p></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                        <div class="feature-text"><h6>Transparent Service</h6><p>Clients receive detailed reports and invoices with full breakdown of parts and labor.</p></div>
                    </div>
                    <div class="feature-item">
                        <div class="feature-icon"><i class="bi bi-people"></i></div>
                        <div class="feature-text"><h6>Expert Team</h6><p>14 certified mechanics specializing in engine, electrical, transmission, brakes, and more.</p></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Services -->
<section id="services">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-tools me-1"></i>What We Do</div>
            <h2 class="section-title">Our Services</h2>
        </div>
        <div class="row g-4">
            <?php foreach ($services as [$icon, $title, $desc]): ?>
            <div class="col-sm-6 col-lg-4">
                <div class="service-card">
                    <div class="service-icon"><i class="bi <?= $icon ?>"></i></div>
                    <h5><?= $title ?></h5>
                    <p><?= $desc ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- What We Do / Why Choose Us -->
<section class="content-section alt" id="what-we-do">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6">
                <div class="section-label"><i class="bi bi-tools me-1"></i>What We Do</div>
                <h3>What We Do</h3>
                <p>At ATECAR GARAGE, we provide comprehensive automotive services to keep your vehicle running smoothly. Our expert technicians handle everything from routine maintenance to complex repairs with precision and care.</p>
                <ul class="tick-list mt-3">
                    <li>Complete vehicle maintenance and servicing</li>
                    <li>Professional diagnostic and repair services</li>
                    <li>Regular oil changes and fluid checks</li>
                    <li>Brake system inspection and repair</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="section-label"><i class="bi bi-award me-1"></i>Why Choose Us</div>
                <h3>Why Choose Us</h3>
                <p>We have 40 skilled technicians in many areas that any garage needs to properly run.</p>
                <ul class="tick-list mt-3">
                    <li>Highly experienced and certified technicians</li>
                    <li>State-of-the-art diagnostic equipment</li>
                    <li>Fast turnaround times without compromising quality</li>
                    <li>Transparent pricing and honest service</li>
                </ul>
            </div>
        </div>
        <div class="row g-3 mt-4 brand-grid">
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-nissan.jpg" alt="Nissan"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-bmw.jpg" alt="BMW"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-mitsubishi.jpg" alt="Mitsubishi"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-mazda.jpg" alt="Mazda"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-opel.jpg" alt="Opel"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-peugeot.jpg" alt="Peugeot"></div>
            <div class="col-6 col-md-4 col-lg"><img src="https://atecargarage.com/wp-content/uploads/2026/05/ate-hyundai.jpg" alt="Hyundai"></div>
        </div>
    </div>
</section>

<!-- About ATECAR GARAGE -->
<section class="content-section" id="about-atecar">
    <div class="container">
        <div class="section-label"><i class="bi bi-building me-1"></i>About ATECAR GARAGE</div>
        <h3>About ATECAR GARAGE</h3>
        <p>Founded on June 1, 2010, ATECAR GARAGE has established itself as a premier automotive service center built on trust, precision, and unwavering dedication to quality. Over two decades of operation, we have grown from a modest workshop into a fully equipped facility trusted by countless motorists and corporate partners alike.</p>
        <p>We specialize in comprehensive mechanical repairs, advanced diagnostics, and preventative maintenance for a wide variety of vehicle makes and models. Our commitment to staying ahead of the curve means we continuously invest in state-of-the-art equipment and ongoing training for our staff.</p>
        <p>Looking to the future, our recent expansion includes dedicated bays and certified technicians specifically trained for EV (Electric Vehicle) and hybrid servicing.</p>
        <div class="about-banner mt-4">
            <img src="https://atecargarage.com/wp-content/uploads/2026/05/Screenshot16-1.webp" alt="ATECAR garage workshop overview">
        </div>
    </div>
</section>

<!-- Gallery -->
<section id="gallery" class="content-section alt">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-images me-1"></i>Gallery</div>
            <h2 class="section-title">Gallery</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.006.jpeg" alt="ATECAR gallery image 1"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.005.jpeg" alt="ATECAR gallery image 2"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.004.jpeg" alt="ATECAR gallery image 3"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.003.jpeg" alt="ATECAR gallery image 4"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.002.jpeg" alt="ATECAR gallery image 5"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.001.jpeg" alt="ATECAR gallery image 6"></div></div>
            <div class="col-md-6 col-lg-4"><div class="gallery-card"><img src="https://atecargarage.com/wp-content/uploads/2026/06/WhatsApp-Image-2026-06-16-at-16.32.00.jpeg" alt="ATECAR gallery image 7"></div></div>
        </div>
    </div>
</section>

<!-- Cars for Sale -->
<section class="content-section" id="cars-for-sale">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-car-front-fill me-1"></i>Cars for Sale</div>
            <h2 class="section-title">Cars for Sale</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-6">
                <div class="sale-card">
                    <a href="https://atecargarage.com/volkswagen-bora-mileage-112-601-year-1999/" target="_blank" rel="noopener noreferrer">Volkswagen Bora - Mileage : 112 601 - Year : 1999</a>
                </div>
            </div>
            <div class="col-md-6">
                <div class="sale-card">
                    <a href="https://atecargarage.com/chevrolet-avalanche-35000-99895-km/" target="_blank" rel="noopener noreferrer">Chevrolet Avalanche - $35,000 - 99895 km</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- What Our Clients Say -->
<section class="content-section alt" id="testimonials">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-chat-quote me-1"></i>What Our Clients Say</div>
            <h2 class="section-title">What Our Clients Say</h2>
        </div>
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="review-card">
                    <p>"They are more organized in their work and the service they provide are excellent and fast."</p>
                    <div class="stars">★★★★★</div>
                    <p class="client">Rosine Uwamariya</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="review-card">
                    <p>"I appreciate the excellent service you have given me."</p>
                    <div class="stars">★★★★★</div>
                    <p class="client">Gaelle "Bataze" Benjamin</p>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="review-card">
                    <p>"Great Garage. Special workers."</p>
                    <div class="stars">★★★★★</div>
                    <p class="client">Mugabo Francis</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Our Past Clients -->
<section class="content-section" id="past-clients">
    <div class="container">
        <div class="text-center mb-5">
            <div class="section-label"><i class="bi bi-briefcase-fill me-1"></i>Our Past Clients</div>
            <h2 class="section-title">Our Past Clients</h2>
        </div>
        <div class="row g-3 past-clients">
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-018.jpg" alt="Past client brand 1"></div>
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-019.jpg" alt="Past client brand 2"></div>
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-020.jpg" alt="Past client brand 3"></div>
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-021.jpg" alt="Past client brand 4"></div>
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-022.jpg" alt="Past client brand 5"></div>
            <div class="col-6 col-md-4 col-lg-2"><img src="https://atecargarage.com/wp-content/uploads/2026/05/brand-011.jpg" alt="Past client brand 6"></div>
        </div>
    </div>
</section>

<!-- Contact, Timing, Quick Links -->
<section class="content-section alt" id="contact-blocks">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4">
                <div class="info-card">
                    <h5>Contact Us</h5>
                    <div class="info-item"><i class="bi bi-geo-alt-fill"></i><span>KK 15 RD / Kicukiro Gahanga<br>Kigali, Rwanda</span></div>
                    <div class="info-item"><i class="bi bi-telephone-fill"></i><span>+(250) 788 308 038<br>+(250) 786 317 111<br>+(250) 788 308 837</span></div>
                    <div class="info-item"><i class="bi bi-envelope-fill"></i><span>info@atecargarage.com</span></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="info-card">
                    <h5>Work Timing</h5>
                    <p>We are open to serve you during the following hours. Appointments outside these hours may be arranged upon request.</p>
                    <div class="info-item"><i class="bi bi-clock-fill"></i><span><strong>Mon - Fri</strong> 08:00 am - 05:00 pm</span></div>
                    <div class="info-item"><i class="bi bi-clock-fill"></i><span><strong>Sat</strong> 08:00 am - 01:00 pm</span></div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="info-card">
                    <h5>Quick Links</h5>
                    <div class="quick-links">
                        <a href="home.php">Home</a>
                        <a href="home.php#about">About Us</a>
                        <a href="home.php#services">Services</a>
                        <a href="home.php#contact-blocks">Contact</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Track Your Vehicle?</h2>
        <p>Log in to view diagnosis reports, service status, and invoices.</p>
        <a href="login.php" class="btn-hero-primary">
            <i class="bi bi-box-arrow-in-right me-2"></i>Login to Your Account
        </a>
    </div>
</section>

<!-- Footer -->
<footer>
    <div class="container">
        <p><strong>VFDSMS</strong> — Vehicle Fault Diagnosis & Service Monitoring System</p>
        <p class="mt-1"><strong><?= htmlspecialchars($garage['garage_name']) ?></strong> &mdash; <?= htmlspecialchars($garage['address']) ?>, <?= htmlspecialchars($garage['district']) ?>, <?= htmlspecialchars($garage['city']) ?>, Rwanda &mdash; <?= htmlspecialchars($garage['phone']) ?></p>
        <p class="mt-2">&copy; <?= date('Y') ?> ATECAR GARAGE. All rights reserved.</p>
    </div>
</footer>

<!-- Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px">
        <div class="modal-content">
            <div class="modal-login-header">
                <div class="logo-icon"><i class="bi bi-car-front-fill"></i></div>
                <h4>VFDSMS</h4>
                <p>Sign in to your account</p>
            </div>
            <div class="modal-body">
                <?php if ($error): ?>
                <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Email or Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                            <input type="text" name="email" class="form-control" placeholder="Enter email or username" required>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Password</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-signin w-100">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                    </button>
                </form>
                <div class="text-center mt-3">
                    <small class="text-muted">No account? to visit the garage</small>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<?php if ($error): ?>
<script>document.addEventListener('DOMContentLoaded',()=>new bootstrap.Modal(document.getElementById('loginModal')).show());</script>
<?php endif; ?>
</body>
</html>
