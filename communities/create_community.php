<?php
session_start();
include '../config/db.php';

// Define base paths
define('FS_BASE', dirname(__DIR__)); // Filesystem base
define('WEB_BASE', '/Gamers_Community/'); // Web base

if (!isset($_SESSION['user_id'])) {
    header("Location: " . WEB_BASE . "auth/login.php");
    exit;
}

$uid = $_SESSION['user_id'];

// Check if header/footer files exist
$header_path = FS_BASE . '/includes/header.php';
$footer_path = FS_BASE . '/includes/footer.php';
$has_header = file_exists($header_path);
$has_footer = file_exists($footer_path);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $created_by = $uid;
    
    // Validate input
    $errors = [];
    if (empty($name)) {
        $errors[] = "Community name is required";
    } elseif (strlen($name) > 100) {
        $errors[] = "Community name must be 100 characters or less";
    }
    
    if (strlen($description) > 65535) {
        $errors[] = "Description is too long";
    }
    
    if (empty($errors)) {
        try {
            // Insert into database
            $stmt = $conn->prepare("INSERT INTO communities (name, description, created_by) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $description, $created_by);
            $stmt->execute();
            
            if ($stmt->affected_rows > 0) {
                $community_id = $stmt->insert_id;
                
                // Add creator as a member
                $stmt = $conn->prepare("INSERT INTO community_members (community_id, user_id) VALUES (?, ?)");
                $stmt->bind_param("ii", $community_id, $created_by);
                $stmt->execute();
                
                $_SESSION['success'] = "Community created successfully!";
                header("Location: " . WEB_BASE . "communities/view_community.php?id=" . $community_id);
                exit();
            } else {
                $errors[] = "Failed to create community";
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) {
                $errors[] = "Community name already exists";
            } else {
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nexus | Create Community</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@700;800&family=Montserrat:wght@400;500;600;700&family=Press+Start+2P&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?= WEB_BASE ?>assets/css/style.css">
    <style>
        .error-message {
            color: #ff4444;
            background: rgba(255, 68, 68, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #ff4444;
        }
        
        .success-message {
            color: #00C851;
            background: rgba(0, 200, 81, 0.1);
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            border-left: 3px solid #00C851;
        }

        .creation-card {
            background: rgba(30, 30, 46, 0.8);
            border-radius: 12px;
            padding: 2rem;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(106, 17, 203, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .creation-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(106, 17, 203, 0.4), 
                        0 0 20px rgba(255, 64, 129, 0.3);
        }

        .card-header {
            margin-bottom: 1.5rem;
            position: relative;
            padding-bottom: 0.8rem;
        }

        .card-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .card-header h2 {
            font-family: 'Orbitron', sans-serif;
            font-size: 2rem;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            letter-spacing: 1px;
        }

        .card-header .subtitle {
            font-size: 0.9rem;
            color: #bbb;
            margin-top: 0.3rem;
            font-family: 'Montserrat', sans-serif;
        }

        .form-section {
            margin-top: 1.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #ddd;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-group label i {
            color: var(--accent);
        }

        .form-control {
            width: 100%;
            padding: 0.9rem;
            background: rgba(20, 20, 36, 0.7);
            border: 1px solid rgba(106, 17, 203, 0.4);
            border-radius: 8px;
            color: white;
            font-family: 'Montserrat', sans-serif;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(255, 64, 129, 0.2);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
        }

        .btn {
            display: inline-block;
            padding: 0.9rem 1.8rem;
            background: linear-gradient(45deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            border-radius: 8px;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: 0.5s;
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(106, 17, 203, 0.5);
        }

        .btn-block {
            display: block;
            width: 100%;
            text-align: center;
        }

        .pulse {
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(106, 17, 203, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(106, 17, 203, 0); }
            100% { box-shadow: 0 0 0 0 rgba(106, 17, 203, 0); }
        }

        .guideline-item {
            background: rgba(20, 20, 36, 0.6);
            border-left: 3px solid var(--accent);
            padding: 1.2rem;
            margin-bottom: 1.5rem;
            border-radius: 0 8px 8px 0;
            transition: all 0.3s ease;
        }

        .guideline-item:hover {
            transform: translateX(5px);
            background: rgba(25, 25, 45, 0.8);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .guideline-header {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            gap: 0.8rem;
        }

        .guideline-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(45deg, var(--primary), var(--accent));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            flex-shrink: 0;
        }

        .guideline-title {
            font-family: 'Orbitron', sans-serif;
            font-size: 1.4rem;
            color: var(--accent);
            letter-spacing: 0.5px;
        }

        .why-matters {
            color: #bbb;
            margin-bottom: 0.8rem;
            font-style: italic;
            padding-left: 0.5rem;
            border-left: 2px solid var(--primary);
        }

        .examples-title {
            font-weight: 600;
            margin: 0.8rem 0 0.5rem;
            color: var(--secondary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .examples-list {
            padding-left: 1.5rem;
            margin-bottom: 0.8rem;
        }

        .examples-list li {
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .key-questions {
            background: rgba(106, 17, 203, 0.15);
            border-radius: 8px;
            padding: 0.8rem;
            margin-top: 1rem;
            border: 1px solid rgba(106, 17, 203, 0.3);
        }

        .key-questions h4 {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: var(--secondary);
        }
    </style>
</head>
<body>
    <?php if ($has_header) include $header_path; ?>

    <div class="container">
        <div class="creation-card">
            <div class="card-header">
                <h2>FORGE YOUR COMMUNITY</h2>
                <div class="subtitle">Shape your corner of the cyberverse</div>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <?php foreach ($errors as $error): ?>
                        <p><i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <form class="form-section" method="POST" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>">
                <div class="form-group">
                    <label for="name"><i class="fas fa-tag"></i> COMMUNITY NAME</label>
                    <input type="text" id="name" name="name" class="form-control" placeholder="e.g. Neon Speedrunners" required
                           value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="description"><i class="fas fa-align-left"></i> DESCRIPTION</label>
                    <textarea id="description" name="description" class="form-control" placeholder="Describe your community's purpose, focus, and vibe..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                </div>
                
                <div class="form-group">
                    <label for="niche"><i class="fas fa-crosshairs"></i> PRIMARY NICHE</label>
                    <select id="niche" name="niche" class="form-control">
                        <option value="">Select a primary focus</option>
                        <option value="fps" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'fps') ? 'selected' : ''; ?>>FPS/Shooters</option>
                        <option value="rpg" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'rpg') ? 'selected' : ''; ?>>RPGs & Story-Driven</option>
                        <option value="strategy" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'strategy') ? 'selected' : ''; ?>>Strategy & Tactics</option>
                        <option value="indie" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'indie') ? 'selected' : ''; ?>>Indie Gems</option>
                        <option value="retro" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'retro') ? 'selected' : ''; ?>>Retro & Classic</option>
                        <option value="esports" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'esports') ? 'selected' : ''; ?>>Competitive & eSports</option>
                        <option value="creative" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'creative') ? 'selected' : ''; ?>>Creative & Building</option>
                        <option value="other" <?php echo (isset($_POST['niche']) && $_POST['niche'] == 'other') ? 'selected' : ''; ?>>Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="games"><i class="fas fa-gamepad"></i> KEY GAMES</label>
                    <input type="text" id="games" name="games" class="form-control" placeholder="e.g. Cyberpunk 2077, Deus Ex, Shadowrun"
                           value="<?php echo isset($_POST['games']) ? htmlspecialchars($_POST['games']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="goal"><i class="fas fa-bullseye"></i> PRIMARY GOAL</label>
                    <select id="goal" name="goal" class="form-control">
                        <option value="">What's your community's purpose?</option>
                        <option value="competitive" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'competitive') ? 'selected' : ''; ?>>Competitive Play & Improvement</option>
                        <option value="casual" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'casual') ? 'selected' : ''; ?>>Casual Gaming & Fun</option>
                        <option value="creative" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'creative') ? 'selected' : ''; ?>>Creative Expression</option>
                        <option value="discovery" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'discovery') ? 'selected' : ''; ?>>Game Discovery & Discussion</option>
                        <option value="support" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'support') ? 'selected' : ''; ?>>Support & Positive Community</option>
                        <option value="lore" <?php echo (isset($_POST['goal']) && $_POST['goal'] == 'lore') ? 'selected' : ''; ?>>Lore & Story Analysis</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="voice"><i class="fas fa-microphone"></i> COMMUNITY VOICE</label>
                    <select id="voice" name="voice" class="form-control">
                        <option value="">Select your community personality</option>
                        <option value="expert" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'expert') ? 'selected' : ''; ?>>Expert & Analytical</option>
                        <option value="fun" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'fun') ? 'selected' : ''; ?>>Fun & Lighthearted</option>
                        <option value="inclusive" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'inclusive') ? 'selected' : ''; ?>>Inclusive & Supportive</option>
                        <option value="hardcore" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'hardcore') ? 'selected' : ''; ?>>Hardcore & Dedicated</option>
                        <option value="creative" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'creative') ? 'selected' : ''; ?>>Creative & Artistic</option>
                        <option value="wholesome" <?php echo (isset($_POST['voice']) && $_POST['voice'] == 'wholesome') ? 'selected' : ''; ?>>Wholesome & Positive</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-block pulse">
                    <i class="fas fa-bolt"></i> IGNITE COMMUNITY
                </button>
            </form>
        </div>
        
        <div class="creation-card">
            <div class="card-header">
                <h2>COMMUNITY DESIGN GUIDE</h2>
                <div class="subtitle">Forge a legendary cyber-community</div>
            </div>
            
            <div class="guideline-item">
                <div class="guideline-header">
                    <div class="guideline-icon">1</div>
                    <h3 class="guideline-title">Clear Niche</h3>
                </div>
                <p class="why-matters">Why it matters: A broad "gaming" page gets lost in the noise. Specificity attracts passionate fans who share your focus.</p>
                
                <h4 class="examples-title"><i class="fas fa-caret-right"></i> Examples & Elaboration</h4>
                <ul class="examples-list">
                    <li><strong>Hyper-Specific:</strong> "Valorant Strategy & Agent Guides for Ranked Players (Gold-Platinum ELO)", "Speedrunning Secrets of Classic Zelda Games (NES/SNES)"</li>
                    <li><strong>Genre-Focused:</strong> "Competitive Apex Legends & Movement Tech", "In-Depth CRPG Analysis (Baldur's Gate, Pathfinder)"</li>
                    <li><strong>Theme-Based:</strong> "Gaming for Mental Wellness & Positive Communities", "Accessibility in Gaming - Hardware & Software Solutions"</li>
                </ul>
                
                <div class="key-questions">
                    <h4><i class="fas fa-question-circle"></i> Key Questions</h4>
                    <p>What games do you genuinely love and know deeply? What gap exists in existing communities? What can you offer uniquely?</p>
                </div>
            </div>
            
            <div class="guideline-item">
                <div class="guideline-header">
                    <div class="guideline-icon">2</div>
                    <h3 class="guideline-title">Defined Goal</h3>
                </div>
                <p class="why-matters">Why it matters: People need a clear reason to engage beyond just "talking about games." What problem do you solve or what experience do you provide?</p>
                
                <h4 class="examples-title"><i class="fas fa-caret-right"></i> Examples & Elaboration</h4>
                <ul class="examples-list">
                    <li><strong>Competitive/Teamplay:</strong> "Find reliable, non-toxic teammates for ranked grind," "Master advanced strategies through VOD reviews"</li>
                    <li><strong>Casual/Community:</strong> "Share hilarious in-game moments and memes," "Discover hidden gem indie games together"</li>
                    <li><strong>Creative/Enthusiast:</strong> "Share and critique fan art/cosplay," "Collaborate on community projects (mods, maps)"</li>
                    <li><strong>Informational:</strong> "Get the fastest, most accurate patch notes and meta breakdowns"</li>
                </ul>
                
                <div class="key-questions">
                    <h4><i class="fas fa-question-circle"></i> Key Questions</h4>
                    <p>What do your ideal members struggle with or crave? What makes them say "Wow, I need this!"? Be specific about the benefit.</p>
                </div>
            </div>
            
            <div class="guideline-item">
                <div class="guideline-header">
                    <div class="guideline-icon">3</div>
                    <h3 class="guideline-title">Unique Voice</h3>
                </div>
                <p class="why-matters">Why it matters: This is your brand's character. It filters who resonates with you and creates a memorable vibe. It should feel authentic to you.</p>
                
                <h4 class="examples-title"><i class="fas fa-caret-right"></i> Examples & Elaboration</h4>
                <ul class="examples-list">
                    <li><strong>Humor/Sarcasm:</strong> "We roast bad plays (lovingly) and meme harder than anyone."</li>
                    <li><strong>Expertise/Deep Dive:</strong> "Serious strategy only. Data-driven analysis, frame-perfect execution guides."</li>
                    <li><strong>Inclusivity/Welcoming:</strong> "A truly safe space for everyone - zero tolerance for toxicity or elitism."</li>
                    <li><strong>Creative/Artistic:</strong> "Where gaming meets art! Focus on aesthetics and storytelling."</li>
                </ul>
                
                <div class="key-questions">
                    <h4><i class="fas fa-question-circle"></i> Key Questions</h4>
                    <p>What's your natural communication style? What values are non-negotiable? What existing communities frustrate you? How do you want members to feel?</p>
                </div>
            </div>
            
            <div class="guideline-item" style="background: rgba(25, 40, 65, 0.6); border-left: 3px solid var(--secondary);">
                <div class="guideline-header">
                    <div class="guideline-icon glow" style="background: linear-gradient(45deg, var(--secondary), #00bcd4);">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <h3 class="guideline-title" style="color: var(--secondary);">Putting It Together</h3>
                </div>
                
                <h4 class="examples-title"><i class="fas fa-caret-right"></i> Example Community Profiles</h4>
                <ul class="examples-list">
                    <li><strong>Niche:</strong> Competitive Apex Legends (Ranked Focus)<br>
                        <strong>Goal:</strong> Find consistent teammates for Diamond+ pushes<br>
                        <strong>Voice:</strong> High-level expertise, respectful but direct</li>
                    <li><strong>Niche:</strong> Cozy & Wholesome Indie Games<br>
                        <strong>Goal:</strong> Discover hidden gems in a positive space<br>
                        <strong>Voice:</strong> Warm, inclusive ("No Salt Zone")</li>
                    <li><strong>Niche:</strong> Retro JRPG Analysis & Speedrunning<br>
                        <strong>Goal:</strong> Deep dives into mechanics and lore<br>
                        <strong>Voice:</strong> Passionate, detail-oriented, nostalgic</li>
                </ul>
            </div>
        </div>
    </div>

    <?php if ($has_footer) include $footer_path; ?>

    <script>
        // Form submission animation
        const form = document.querySelector('form');
        if (form) {
            form.addEventListener('submit', function(e) {
                const submitBtn = this.querySelector('button[type="submit"]');
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> FORGING COMMUNITY...';
                submitBtn.disabled = true;
            });
        }
        
        // Animate guideline items on scroll
        document.addEventListener('DOMContentLoaded', function() {
            const guidelineItems = document.querySelectorAll('.guideline-item');
            
            guidelineItems.forEach((item, index) => {
                setTimeout(() => {
                    item.style.opacity = '0';
                    item.style.transform = 'translateX(-20px)';
                    item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                    
                    setTimeout(() => {
                        item.style.opacity = '1';
                        item.style.transform = 'translateX(0)';
                    }, 100);
                }, index * 150);
            });
        });
    </script>
</body>
</html>