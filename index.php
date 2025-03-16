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
    <title>Juicy Quiz Game</title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2ecc71;
            --background-color: #f5f6fa;
            --text-color: #2c3e50;
            --shadow: 0 2px 5px rgba(0,0,0,0.1);
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            background-color: var(--background-color);
            color: var(--text-color);
            line-height: 1.6;
        }

        .game-container {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: var(--shadow);
            margin: 0 auto;
            max-width: 800px;
        }

        h1 {
            color: var(--primary-color);
            text-align: center;
            margin-bottom: 20px;
            font-size: 2.2em;
        }

        .choices {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin: 20px auto;
            max-width: 600px;
        }

        .choice-btn {
            padding: 15px 20px;
            font-size: 16px;
            cursor: pointer;
            background-color: white;
            border: 2px solid var(--primary-color);
            border-radius: 8px;
            transition: all 0.2s ease;
            color: var(--primary-color);
        }

        .choice-btn:hover {
            background-color: var(--primary-color);
            color: white;
        }

        .score {
            font-size: 24px;
            margin: 20px 0;
            color: var(--primary-color);
            text-align: center;
        }

        .question-count {
            font-size: 18px;
            margin-bottom: 20px;
            color: var(--text-color);
            text-align: center;
        }

        .high-scores {
            margin-top: 30px;
            background: white;
            border-radius: 8px;
            padding: 20px;
        }

        .high-scores h2 {
            color: var(--primary-color);
            margin-bottom: 15px;
            text-align: center;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
            background-color: white;
        }

        th {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            text-align: left;
        }

        td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover td {
            background-color: #f8f9fa;
        }

        input[type="text"] {
            padding: 12px 20px;
            margin: 15px 0;
            border: 2px solid #ddd;
            border-radius: 6px;
            font-size: 16px;
            width: 250px;
            display: block;
            margin: 15px auto;
        }

        input[type="text"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button, .button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.2s;
            display: inline-block;
            text-decoration: none;
        }

        button:hover, .button:hover {
            background-color: #2980b9;
        }

        .result-message {
            font-size: 22px;
            margin: 20px 0;
            padding: 15px;
            border-radius: 8px;
            background-color: #f1f8ff;
            color: var(--primary-color);
            text-align: center;
        }

        .game-info {
            text-align: center;
            margin: 15px 0;
            color: #666;
        }

        .fruit-image {
            max-width: 200px;
            margin: 20px auto;
            display: block;
            border-radius: 8px;
            box-shadow: var(--shadow);
        }

        .text-center {
            text-align: center;
        }
    </style>
</head>
<body>
    <?php if (!isset($_SESSION['game_started']) && !isset($_GET['show_results'])): ?>
        <!-- Start Screen -->
        <div class="game-container">
            <h1>Juicy Quiz Game</h1>
            <div class="game-info">
                Can you identify all 10 fruits? Test your knowledge!
            </div>
            <form method="POST" class="text-center">
                <input type="text" name="username" placeholder="Enter your username" required>
                <button type="submit">Start Game</button>
            </form>
            
            <div class="high-scores">
                <h2>High Scores</h2>
                <table>
                    <tr>
                        <th>#</th>
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
                        <td><?php echo $rank++; ?></td>
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
            <h1>Game Over!</h1>
            <div class="result-message">
                Your Score: <?php echo $_SESSION['final_score']; ?>/10
            </div>
            <div class="text-center">
                <a href="index.php" class="button">Play Again</a>
            </div>
            
            <div class="high-scores">
                <h2>High Scores</h2>
                <table>
                    <tr>
                        <th>#</th>
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
                        <td><?php echo $rank++; ?></td>
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
            <div class="score">Score: <?php echo $_SESSION['score']; ?>/10</div>
            <div class="question-count">Question <?php echo $_SESSION['question_count'] + 1; ?> of 10</div>
            
            <?php
            $fruits = ['Apple', 'Banana', 'Orange', 'Grape', 'Strawberry', 'Mango', 'Pineapple', 'Watermelon', 'Kiwi', 'Peach', 'Pear', 'Plum'];
            
            // Get unused fruits
            $available_fruits = array_diff($fruits, $_SESSION['used_fruits']);
            
            if (empty($available_fruits)) {
                $_SESSION['used_fruits'] = [];
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
