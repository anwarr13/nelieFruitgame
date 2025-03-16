<?php
session_start();
require_once 'db.php';

// 
if (!isset($_SESSION['game_started']) && isset($_POST['username'])) {
    $_SESSION['username'] = $_POST['username']; // ngan
    $_SESSION['score'] = 0; // iyang score
    $_SESSION['question_count'] = 0; // pila ka pangutana 
    $_SESSION['game_started'] = true; // mag sugod ug duwa
    $_SESSION['time_started'] = date('Y-m-d H:i:s'); // kung unsa siya oras ga sugod ug duwa
    $_SESSION['used_fruits'] = []; // para di balik2 ang mga fruits
}

// Game completion logic
function endGame($conn) {
    $timeEnded = date('Y-m-d H:i:s');
    $timeStarted = $_SESSION['time_started'];
    $duration = strtotime($timeEnded) - strtotime($timeStarted);
    $finalScore = $_SESSION['score']; // Store the final score
    $datePlayed = date('Y-m-d'); // Get current date
    
    $stmt = $conn->prepare("INSERT INTO players (username, score, time_started, time_ended, duration_seconds, date_played) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sissss", $_SESSION['username'], $_SESSION['score'], $_SESSION['time_started'], $timeEnded, $duration, $datePlayed);
    $stmt->execute();
    $stmt->close();
    
    session_destroy();
    session_start(); // Start a new session
    $_SESSION['final_score'] = $finalScore; // Save the final score in the new session
}

// Handle answer submission
if (isset($_POST['answer'])) {
    if ($_POST['answer'] == $_SESSION['correct_fruit']) {
        $_SESSION['score']++;
    }
    $_SESSION['question_count']++;
    
    if ($_SESSION['question_count'] >= 10) {
        endGame($conn);
        header('Location: index.php?show_results=1');
        exit();
    }
}

// Get high scores
$highScores = [];
$result = $conn->query("SELECT username, score, duration_seconds as time, DATE_FORMAT(date_played, '%m/%d/%Y') as date_played FROM players ORDER BY score DESC, duration_seconds ASC LIMIT 100");
if ($result) {
    while ($row = $result->fetch_object()) {
        $highScores[] = $row;
    }
    $result->free();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Fruit Quiz Game</title>
    <style>
        :root {
            --primary-color: #FF6B6B;
            --secondary-color: #4ECDC4;
            --accent-color: #FFE66D;
            --background-color: #f0f2f5;
            --text-color: #2C3E50;
            --shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        body {
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            background: linear-gradient(135deg, var(--background-color) 0%, #ffffff 100%);
            color: var(--text-color);
            line-height: 1.6;
        }

        .game-container {
            background-color: white;
            border-radius: 24px;
            padding: 40px;
            box-shadow: var(--shadow);
            margin: 20px auto;
            max-width: 800px;
            position: relative;
            overflow: hidden;
        }

        .game-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 6px;
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
        }

        .game-title {
            margin-bottom: 40px;
            text-align: center;
        }

        h1 {
            color: var(--text-color);
            margin-bottom: 20px;
            font-size: 3em;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-align: center;
        }

        .choices {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .choice-btn {
            padding: 20px 30px;
            font-size: 18px;
            cursor: pointer;
            background-color: white;
            border: 3px solid var(--secondary-color);
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            color: var(--text-color);
            font-weight: 600;
            position: relative;
            overflow: hidden;
        }

        .choice-btn:hover {
            background-color: var(--secondary-color);
            color: white;
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(78, 205, 196, 0.2);
        }

        .choice-btn:active {
            transform: translateY(-2px);
        }

        .score {
            font-size: 32px;
            margin: 30px 0;
            color: var(--primary-color);
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .score i {
            color: var(--accent-color);
            animation: pulse 2s infinite;
        }

        .question-count {
            font-size: 20px;
            margin-bottom: 30px;
            color: var(--text-color);
            font-weight: 500;
            text-align: center;
            opacity: 0.8;
        }

        .high-scores {
            margin-top: 40px;
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: var(--shadow);
        }

        .high-scores h2 {
            color: var(--text-color);
            margin-bottom: 20px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 20px 0;
            background-color: white;
            border-radius: 16px;
            overflow: hidden;
        }

        th {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            padding: 15px 20px;
            text-transform: uppercase;
            font-size: 14px;
            letter-spacing: 1px;
        }

        td {
            padding: 15px 20px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            transition: background-color 0.3s;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: rgba(78, 205, 196, 0.05);
        }

        input[type="text"] {
            padding: 15px 25px;
            margin: 20px 0;
            border: 2px solid #e1e1e1;
            border-radius: 12px;
            font-size: 16px;
            width: 280px;
            transition: all 0.3s ease;
            background-color: white;
        }

        input[type="text"]:focus {
            border-color: var(--secondary-color);
            outline: none;
            box-shadow: 0 0 0 4px rgba(78, 205, 196, 0.1);
        }

        button, .button {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        button:hover, .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        button:active, .button:active {
            transform: translateY(0);
        }

        .result-message {
            font-size: 28px;
            margin: 30px 0;
            padding: 20px;
            border-radius: 16px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            text-align: center;
            animation: slideIn 0.5s ease-out;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.2); }
            100% { transform: scale(1); }
        }

        @keyframes slideIn {
            from { transform: translateY(-20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .game-info {
            text-align: center;
            margin-bottom: 30px;
            color: var(--text-color);
            opacity: 0.8;
        }

        .fruit-image {
            max-width: 200px;
            margin: 20px auto;
            border-radius: 20px;
            box-shadow: var(--shadow);
            transition: transform 0.3s ease;
        }

        .fruit-image:hover {
            transform: scale(1.05);
        }
    </style>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <!-- Start Screen -->
        <div class="game-container">
            <div class="game-title">
                <h1>🍎 Fruit Quiz Game 🍊</h1>
                <div class="game-info">
                    Test your fruit knowledge! Can you identify all 10 fruits correctly?
                </div>
            </div>
            <form method="POST" style="text-align: center;">
                <input type="text" name="username" placeholder="Enter your username" required>
                <br>
                <button type="submit">Start Game <i class="fas fa-play"></i></button>
            </form>
            
            <div class="high-scores">
                <h2>🏆 High Scores</h2>
                <table>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Time</th>
                        <th>Date</th>
                    </tr>
                    <?php 
                    $rank = 1;
                    foreach ($highScores as $score): 
                    ?>
                    <tr>
                        <td>#<?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($score->username); ?></td>
                        <td><?php echo $score->score; ?>/10</td>
                        <td><?php echo $score->time; ?>s</td>
                        <td><?php echo $score->date_played; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php elseif (isset($_GET['show_results'])): ?>
        <!-- Results Screen -->
        <div class="game-container">
            <h1>Game Over! 🎮</h1>
            <div class="result-message">
                Final Score: <?php echo $_SESSION['final_score']; ?>/10
            </div>
            <div style="text-align: center; margin-top: 30px;">
                <a href="index.php" class="button">Play Again <i class="fas fa-redo"></i></a>
            </div>
            
            <div class="high-scores">
                <h2>🏆 High Scores</h2>
                <table>
                    <tr>
                        <th>Rank</th>
                        <th>Player</th>
                        <th>Score</th>
                        <th>Time</th>
                        <th>Date</th>
                    </tr>
                    <?php 
                    $rank = 1;
                    foreach ($highScores as $score): 
                    ?>
                    <tr>
                        <td>#<?php echo $rank++; ?></td>
                        <td><?php echo htmlspecialchars($score->username); ?></td>
                        <td><?php echo $score->score; ?>/10</td>
                        <td><?php echo $score->time; ?>s</td>
                        <td><?php echo $score->date_played; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    <?php else: ?>
        <!-- Game Screen -->
        <div class="game-container">
            <div class="score"><i class="fas fa-star"></i> Score: <?php echo $_SESSION['score']; ?>/10</div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            
            <?php
            $fruits = ['Apple', 'Banana', 'Orange', 'Grape', 'Strawberry', 'Mango', 'Pineapple', 'Watermelon', 'Kiwi', 'Peach', 'Pear', 'Plum'];
            
            // Get unused fruits
            $available_fruits = array_diff($fruits, $_SESSION['used_fruits']);
            
            if (empty($available_fruits)) {
                $_SESSION['used_fruits'] = []; // Reset if all fruits are used
                $available_fruits = $fruits;
            }
            
            // Select random fruit
            $correct_fruit = array_rand(array_flip($available_fruits));
            $_SESSION['used_fruits'][] = $correct_fruit;
            $_SESSION['correct_fruit'] = $correct_fruit;
            
            // Get 3 random wrong answers
            $wrong_fruits = array_diff($fruits, [$correct_fruit]);
            shuffle($wrong_fruits);
            $wrong_fruits = array_slice($wrong_fruits, 0, 3);
            
            // Combine and shuffle all choices
            $choices = array_merge([$correct_fruit], $wrong_fruits);
            shuffle($choices);
            ?>
            
            <img src="images/<?php echo strtolower($correct_fruit); ?>.jpg" alt="Fruit" class="fruit-image">
            
            <form method="POST" class="choices">
                <?php foreach ($choices as $fruit): ?>
                <button type="submit" name="answer" value="<?php echo $fruit; ?>" class="choice-btn">
                    <?php echo $fruit; ?>
                </button>
                <?php endforeach; ?>
            </form>
        </div>
    <?php endif; ?>
</body>
</html>