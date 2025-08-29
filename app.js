// Snooker Shot Tracker - Main JavaScript

// Global variables
let currentGameId = null;
let currentPlayerId = null;
let player1Id = null;
let player2Id = null;
let gameStats = {
    totalShots: 0,
    good: 0,
    ok: 0,
    bad: 0,
    player1: { good: 0, ok: 0, bad: 0 },
    player2: { good: 0, ok: 0, bad: 0 }
};
let pendingShot = null;

// Initialize app when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadPlayers();
});

// Tab switching function
function switchTab(tabName, element = null) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.tab').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab content
    document.getElementById(tabName + 'Tab').classList.add('active');
    
    // Add active class to clicked tab
    if (element) {
        element.classList.add('active');
    }
    
    // Load match history when history tab is clicked
    if (tabName === 'history') {
        loadMatchHistory();
    }
}

// Load players from API
async function loadPlayers() {
    try {
        const response = await fetch('api/get_players.php');
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const players = JSON.parse(text);
        
        if (players.error) {
            throw new Error(players.error);
        }
        
        const player1Select = document.getElementById('player1');
        const player2Select = document.getElementById('player2');
        
        // Clear existing options
        player1Select.innerHTML = '<option value="">Select Player 1</option>';
        player2Select.innerHTML = '<option value="">Practice Mode</option>';
        
        if (Array.isArray(players) && players.length > 0) {
            players.forEach(player => {
                player1Select.innerHTML += `<option value="${player.id}">${player.name}</option>`;
                player2Select.innerHTML += `<option value="${player.id}">${player.name}</option>`;
            });
            displayPlayers(players);
        } else {
            displayPlayers([]);
        }
        
    } catch (error) {
        console.error('Error loading players:', error);
        showError('setupError', 'Error loading players: ' + error.message);
        document.getElementById('player1').innerHTML = '<option value="">Error loading players</option>';
    }
}

// Display players in the manage players tab
function displayPlayers(players) {
    const playersDisplay = document.getElementById('playersDisplay');
    
    if (players.length === 0) {
        playersDisplay.innerHTML = '<p>No players found. Add some players to get started!</p>';
        return;
    }

    let html = '<div class="stats-grid">';
    players.forEach(player => {
        html += `
            <div class="stat-card">
                <div style="font-weight: bold; margin-bottom: 10px;">${player.name}</div>
                <div class="stat-label">${player.email || 'No email'}</div>
                <div class="stat-label">Added: ${new Date(player.created_at).toLocaleDateString()}</div>
            </div>
        `;
    });
    html += '</div>';
    playersDisplay.innerHTML = html;
}

// Start a new game
async function startGame() {
    player1Id = document.getElementById('player1').value;
    player2Id = document.getElementById('player2').value || null;
    
    if (!player1Id) {
        showError('setupError', 'Please select at least Player 1');
        return;
    }

    try {
        const response = await fetch('api/start_game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ player1_id: player1Id, player2_id: player2Id })
        });
        
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const result = JSON.parse(text);
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        currentGameId = result.game_id;
        currentPlayerId = player1Id;
        
        setupGameInterface();
        document.getElementById('playTabButton').style.display = 'block';
        switchTab('play');
        showSuccess('setupError', 'Game started successfully!');
        
        setTimeout(() => {
            document.getElementById('setupError').innerHTML = '';
        }, 3000);
        
    } catch (error) {
        console.error('Error starting game:', error);
        showError('setupError', 'Error starting game: ' + error.message);
    }
}

// Setup game interface
function setupGameInterface() {
    const player1Name = document.getElementById('player1').selectedOptions[0].text;
    const player2Name = player2Id ? document.getElementById('player2').selectedOptions[0].text : null;
    
    document.getElementById('player1Name').textContent = player1Name;
    document.getElementById('currentPlayer').textContent = player1Name;
    
    if (player2Id) {
        document.getElementById('player2Name').textContent = player2Name;
        document.getElementById('player2Card').style.display = 'block';
        document.getElementById('switchPlayerBtn').style.display = 'inline-block';
    } else {
        document.getElementById('player2Card').style.display = 'none';
        document.getElementById('switchPlayerBtn').style.display = 'none';
    }
    
    // Reset stats
    gameStats = {
        totalShots: 0,
        good: 0,
        ok: 0,
        bad: 0,
        player1: { good: 0, ok: 0, bad: 0 },
        player2: { good: 0, ok: 0, bad: 0 }
    };
    
    updateStatsDisplay();
    updateActivePlayer();
    document.getElementById('gameArea').style.display = 'block';
    document.getElementById('shotHistory').innerHTML = '<div class="loading">No shots recorded yet...</div>';
}

// Quick shot recording flow
let quickShot = {
    type: null,
    ball: null,
    result: null,
    step: 1
};

// Step 1: Select shot type
function selectShotType(type) {
    quickShot.type = type;
    quickShot.step = 2;
    
    document.getElementById('selectedShotType').textContent = type.charAt(0).toUpperCase() + type.slice(1);
    
    if (type === 'pot') {
        // Show ball selection for pots
        showStep(2);
    } else {
        // Skip ball selection for safety/snooker, go directly to result
        quickShot.ball = null;
        quickShot.step = 3;
        updateShotDisplay();
        showStep(3);
    }
}

// Step 2: Select ball (only for pots)
function selectBall(ball) {
    quickShot.ball = ball;
    quickShot.step = 3;
    updateShotDisplay();
    showStep(3);
}

// Step 3: Select result
function selectResult(result) {
    quickShot.result = result;
    quickShot.step = 4;
    updateFinalSummary();
    showStep(4);
}

// Navigate between steps
function goBackToStep(stepNumber) {
    quickShot.step = stepNumber;
    showStep(stepNumber);
}

// Show specific step
function showStep(stepNumber) {
    // Hide all steps
    document.querySelectorAll('.shot-step').forEach(step => {
        step.classList.remove('active');
    });
    
    // Show current step
    document.getElementById('step' + stepNumber).classList.add('active');
    
    // Update back button for safety/snooker shots
    if (stepNumber === 3 && quickShot.type !== 'pot') {
        const backBtn = document.querySelector('#step3 .flow-btn.back');
        backBtn.setAttribute('onclick', 'goBackToStep(1)');
    }
}

// Update shot display for step 3
function updateShotDisplay() {
    let shotText = quickShot.type.charAt(0).toUpperCase() + quickShot.type.slice(1);
    if (quickShot.ball) {
        const ballPoints = getBallPoints(quickShot.ball);
        shotText += ' ' + quickShot.ball.charAt(0).toUpperCase() + quickShot.ball.slice(1) + ' (' + ballPoints + ')';
    }
    document.getElementById('selectedShotDisplay').textContent = shotText;
}

// Update final summary for step 4
function updateFinalSummary() {
    document.getElementById('finalShotType').textContent = quickShot.type.charAt(0).toUpperCase() + quickShot.type.slice(1);
    
    if (quickShot.ball) {
        const ballPoints = getBallPoints(quickShot.ball);
        document.getElementById('finalBall').textContent = quickShot.ball.charAt(0).toUpperCase() + quickShot.ball.slice(1) + ' (' + ballPoints + ')';
        document.getElementById('finalBall').style.display = 'inline-block';
    } else {
        document.getElementById('finalBall').style.display = 'none';
    }
    
    document.getElementById('finalResult').textContent = quickShot.result.charAt(0).toUpperCase() + quickShot.result.slice(1);
    document.getElementById('finalResult').className = 'selection-item quality-' + quickShot.result;
}

// Get points for ball
function getBallPoints(ball) {
    const points = {
        'red': 1,
        'yellow': 2,
        'green': 3,
        'brown': 4,
        'blue': 5,
        'pink': 6,
        'black': 7
    };
    return points[ball] || 0;
}

// Reset shot flow
function resetShotFlow() {
    quickShot = { type: null, ball: null, result: null, step: 1 };
    showStep(1);
}

// Confirm and record the quick shot
async function confirmQuickShot() {
    if (!quickShot.type || !quickShot.result) {
        alert('Please complete all shot details');
        return;
    }

    // Calculate points - only award points for successful pots
    let points = 0;
    if (quickShot.type === 'pot' && quickShot.result === 'good' && quickShot.ball) {
        points = getBallPoints(quickShot.ball);
    }

    const shotData = {
        game_id: currentGameId,
        player_id: currentPlayerId,
        shot_quality: quickShot.result,
        shot_type: quickShot.type,
        ball_targeted: quickShot.ball,
        points: points,
        notes: '' // No notes in quick flow
    };

    try {
        const response = await fetch('api/record_shot.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(shotData)
        });
        
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const result = JSON.parse(text);
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        if (result.success) {
            updateGameStats(quickShot.result, currentPlayerId, points);
            addToShotHistory(shotData);
            resetShotFlow();
            
            // Auto-switch player after certain shot types (except for practice mode)
            if (player2Id && ['miss', 'safety'].includes(quickShot.type) || (quickShot.type === 'pot' && quickShot.result === 'bad')) {
                setTimeout(switchPlayer, 1000);
            }
        }
    } catch (error) {
        console.error('Error recording shot:', error);
        alert('Error recording shot: ' + error.message);
    }
}

// Update game statistics
function updateGameStats(quality, playerId, points = 0) {
    gameStats.totalShots++;
    gameStats[quality]++;
    
    const playerKey = playerId === player1Id ? 'player1' : 'player2';
    gameStats[playerKey][quality]++;
    
    // Update scores if points were scored
    if (points > 0) {
        if (playerId === player1Id) {
            const currentScore = parseInt(document.getElementById('player1Score').textContent) || 0;
            document.getElementById('player1Score').textContent = currentScore + points;
        } else if (player2Id && playerId === player2Id) {
            const currentScore = parseInt(document.getElementById('player2Score').textContent) || 0;
            document.getElementById('player2Score').textContent = currentScore + points;
        }
    }
    
    updateStatsDisplay();
}

// Update statistics display
function updateStatsDisplay() {
    document.getElementById('totalShots').textContent = gameStats.totalShots;
    
    if (gameStats.totalShots > 0) {
        document.getElementById('goodPercentage').textContent = 
            Math.round((gameStats.good / gameStats.totalShots) * 100) + '%';
        document.getElementById('okPercentage').textContent = 
            Math.round((gameStats.ok / gameStats.totalShots) * 100) + '%';
        document.getElementById('badPercentage').textContent = 
            Math.round((gameStats.bad / gameStats.totalShots) * 100) + '%';
    }
    
    // Update player-specific stats
    document.getElementById('p1Good').textContent = gameStats.player1.good;
    document.getElementById('p1Ok').textContent = gameStats.player1.ok;
    document.getElementById('p1Bad').textContent = gameStats.player1.bad;
    
    if (player2Id) {
        document.getElementById('p2Good').textContent = gameStats.player2.good;
        document.getElementById('p2Ok').textContent = gameStats.player2.ok;
        document.getElementById('p2Bad').textContent = gameStats.player2.bad;
    }
}

// Add shot to history display
function addToShotHistory(shotData) {
    const historyDiv = document.getElementById('shotHistory');
    const playerName = shotData.player_id === player1Id ? 
        document.getElementById('player1Name').textContent : 
        document.getElementById('player2Name').textContent;
    
    const shotItem = document.createElement('div');
    shotItem.className = 'shot-item';
    shotItem.innerHTML = `
        <div class="shot-info">
            <strong>${playerName}</strong> - ${shotData.shot_type.charAt(0).toUpperCase() + shotData.shot_type.slice(1)} 
            ${shotData.ball_targeted ? '(' + shotData.ball_targeted + ')' : ''}
            ${shotData.points > 0 ? ' +' + shotData.points + ' pts' : ''}
            ${shotData.notes ? '<br><small>' + shotData.notes + '</small>' : ''}
        </div>
        <div class="shot-quality quality-${shotData.shot_quality}">
            ${shotData.shot_quality.toUpperCase()}
        </div>
    `;
    
    // Remove loading message if it exists
    const loadingMsg = historyDiv.querySelector('.loading');
    if (loadingMsg) {
        loadingMsg.remove();
    }
    
    historyDiv.insertBefore(shotItem, historyDiv.firstChild);
    
    // Keep only last 15 shots visible
    while (historyDiv.children.length > 15) {
        historyDiv.removeChild(historyDiv.lastChild);
    }
}

// Switch between players
function switchPlayer() {
    if (!player2Id) {
        alert('This is a practice session. Only one player.');
        return;
    }
    
    currentPlayerId = currentPlayerId === player1Id ? player2Id : player1Id;
    updateActivePlayer();
}

// Update active player display
function updateActivePlayer() {
    const player1Card = document.getElementById('player1Card');
    const player2Card = document.getElementById('player2Card');
    const currentPlayerSpan = document.getElementById('currentPlayer');
    
    if (currentPlayerId === player1Id) {
        player1Card.classList.add('active');
        if (player2Id) player2Card.classList.remove('active');
        currentPlayerSpan.textContent = document.getElementById('player1Name').textContent;
    } else {
        player1Card.classList.remove('active');
        player2Card.classList.add('active');
        currentPlayerSpan.textContent = document.getElementById('player2Name').textContent;
    }
}

// End current game
async function endGame() {
    if (!confirm('Are you sure you want to end this game?')) return;
    
    const player1Score = parseInt(document.getElementById('player1Score').textContent) || 0;
    const player2Score = parseInt(document.getElementById('player2Score').textContent) || 0;
    let winnerId = null;
    
    if (player2Id) {
        winnerId = player1Score > player2Score ? player1Id : 
                  (player2Score > player1Score ? player2Id : null);
    }
    
    try {
        const response = await fetch('api/end_game.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                game_id: currentGameId,
                winner_id: winnerId,
                score_p1: player1Score,
                score_p2: player2Score
            })
        });
        
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const result = JSON.parse(text);
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        if (result.success) {
            showGameSummary();
        }
    } catch (error) {
        console.error('Error ending game:', error);
        alert('Error ending game: ' + error.message);
    }
}

// Show game summary and reset
function showGameSummary() {
    const player1Name = document.getElementById('player1Name').textContent;
    const player2Name = player2Id ? document.getElementById('player2Name').textContent : null;
    const player1Score = parseInt(document.getElementById('player1Score').textContent) || 0;
    const player2Score = parseInt(document.getElementById('player2Score').textContent) || 0;
    
    let winner = '';
    if (player2Id) {
        if (player1Score > player2Score) {
            winner = `🏆 Winner: ${player1Name} (${player1Score} - ${player2Score})`;
        } else if (player2Score > player1Score) {
            winner = `🏆 Winner: ${player2Name} (${player2Score} - ${player1Score})`;
        } else {
            winner = `🤝 Draw! (${player1Score} - ${player2Score})`;
        }
    } else {
        winner = `📊 Practice session complete! Final score: ${player1Score}`;
    }
    
    const summary = `Game Complete! 🎱

${winner}

SHOT STATISTICS:
━━━━━━━━━━━━━━━━━━━━━━━━━
Total Shots: ${gameStats.totalShots}
Good Shots: ${gameStats.good} (${gameStats.totalShots > 0 ? Math.round((gameStats.good / gameStats.totalShots) * 100) : 0}%)
OK Shots: ${gameStats.ok} (${gameStats.totalShots > 0 ? Math.round((gameStats.ok / gameStats.totalShots) * 100) : 0}%)
Bad Shots: ${gameStats.bad} (${gameStats.totalShots > 0 ? Math.round((gameStats.bad / gameStats.totalShots) * 100) : 0}%)

${player1Name} Performance:
Good: ${gameStats.player1.good} | OK: ${gameStats.player1.ok} | Bad: ${gameStats.player1.bad}

${player2Id ? `${player2Name} Performance:\nGood: ${gameStats.player2.good} | OK: ${gameStats.player2.ok} | Bad: ${gameStats.player2.bad}` : ''}

Great game! Keep practicing! 🎯`;
    
    alert(summary);
    
    // Reset game
    currentGameId = null;
    currentPlayerId = null;
    player1Id = null;
    player2Id = null;
    document.getElementById('gameArea').style.display = 'none';
    document.getElementById('playTabButton').style.display = 'none';
    
    // Switch back to setup tab
    switchTab('setup', document.querySelector('.tab'));
}

// Load match history
async function loadMatchHistory() {
    try {
        document.getElementById('matchHistoryTable').innerHTML = '<div class="loading">Loading match history...</div>';
        document.getElementById('playerStatsTable').innerHTML = '<div class="loading">Loading statistics...</div>';
        
        const response = await fetch('api/get_match_history.php');
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const data = JSON.parse(text);
        
        if (data.error) {
            throw new Error(data.error);
        }
        
        displayPlayerStats(data.player_stats);
        displayMatchHistory(data.games);
        
    } catch (error) {
        console.error('Error loading match history:', error);
        document.getElementById('matchHistoryTable').innerHTML = `<div class="error">Error loading match history: ${error.message}</div>`;
        document.getElementById('playerStatsTable').innerHTML = `<div class="error">Error loading statistics: ${error.message}</div>`;
    }
}

// Display player statistics table
function displayPlayerStats(playerStats) {
    if (!playerStats || playerStats.length === 0) {
        document.getElementById('playerStatsTable').innerHTML = '<p>No player statistics available.</p>';
        return;
    }

    let html = `
        <table class="match-table">
            <thead>
                <tr>
                    <th>Player</th>
                    <th>Games</th>
                    <th>Total Shots</th>
                    <th>Good %</th>
                    <th>OK %</th>
                    <th>Bad %</th>
                    <th>Avg Points</th>
                </tr>
            </thead>
            <tbody>
    `;

    playerStats.forEach(player => {
        const stats = player.stats || {};
        const totalShots = parseInt(stats.total_shots) || 0;
        const goodShots = parseInt(stats.good_shots) || 0;
        const okShots = parseInt(stats.ok_shots) || 0;
        const badShots = parseInt(stats.bad_shots) || 0;
        const totalPoints = parseInt(stats.total_points) || 0;
        const gamesPlayed = parseInt(stats.games_played) || 0;

        const goodPct = totalShots > 0 ? Math.round((goodShots / totalShots) * 100) : 0;
        const okPct = totalShots > 0 ? Math.round((okShots / totalShots) * 100) : 0;
        const badPct = totalShots > 0 ? Math.round((badShots / totalShots) * 100) : 0;
        const avgPoints = gamesPlayed > 0 ? Math.round(totalPoints / gamesPlayed) : 0;

        html += `
            <tr>
                <td><strong>${player.name}</strong></td>
                <td>${gamesPlayed}</td>
                <td>${totalShots}</td>
                <td><span style="color: #4CAF50">${goodPct}%</span></td>
                <td><span style="color: #FF9800">${okPct}%</span></td>
                <td><span style="color: #f44336">${badPct}%</span></td>
                <td>${avgPoints}</td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    document.getElementById('playerStatsTable').innerHTML = html;
}

// Display match history table
function displayMatchHistory(games) {
    if (!games || games.length === 0) {
        document.getElementById('matchHistoryTable').innerHTML = '<p>No match history available.</p>';
        return;
    }

    let html = `
        <table class="match-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Players</th>
                    <th>Score</th>
                    <th>Total Shots</th>
                    <th>Winner Success Rate</th>
                    <th>Winner</th>
                </tr>
            </thead>
            <tbody>
    `;

    games.forEach(game => {
        const date = new Date(game.created_at).toLocaleDateString();
        const player1 = game.player1_name;
        const player2 = game.player2_name || 'Practice';
        const score1 = game.final_score_p1 || 0;
        const score2 = game.final_score_p2 || 0;
        const totalShots = game.total_player1_shots + game.total_player2_shots;
        
        let winner = 'Draw';
        let winnerClass = '';
        let winnerRate = 0;
        
        if (game.winner_id) {
            if (game.winner_id == game.player1_id) {
                winner = player1;
                winnerRate = game.player1_success_rate;
                winnerClass = 'winner';
            } else if (game.winner_id == game.player2_id) {
                winner = player2;
                winnerRate = game.player2_success_rate;
                winnerClass = 'winner';
            }
        } else if (!game.player2_id) {
            winner = 'Practice Complete';
            winnerRate = game.player1_success_rate;
        }

        const scoreDisplay = game.player2_id ? `${score1} - ${score2}` : score1;
        const playersDisplay = game.player2_id ? `${player1} vs ${player2}` : `${player1} (Practice)`;

        html += `
            <tr>
                <td>${date}</td>
                <td>${playersDisplay}</td>
                <td><strong>${scoreDisplay}</strong></td>
                <td>${totalShots}</td>
                <td>${winnerRate}%</td>
                <td class="${winnerClass}"><strong>${winner}</strong></td>
            </tr>
        `;
    });

    html += '</tbody></table>';
    document.getElementById('matchHistoryTable').innerHTML = html;
}

// Player management functions
function toggleAddPlayer() {
    const form = document.getElementById('addPlayerForm');
    form.classList.toggle('show');
    if (form.classList.contains('show')) {
        document.getElementById('newPlayerName').focus();
    } else {
        document.getElementById('newPlayerName').value = '';
        document.getElementById('newPlayerEmail').value = '';
        document.getElementById('addPlayerResult').innerHTML = '';
    }
}

async function addNewPlayer() {
    const name = document.getElementById('newPlayerName').value.trim();
    const email = document.getElementById('newPlayerEmail').value.trim();
    
    if (!name) {
        showError('addPlayerResult', 'Player name is required');
        return;
    }
    
    try {
        const response = await fetch('api/add_player.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ name: name, email: email || null })
        });
        
        const text = await response.text();
        
        if (!text || text.trim() === '') {
            throw new Error('Empty response from server');
        }
        
        const result = JSON.parse(text);
        
        if (result.error) {
            throw new Error(result.error);
        }
        
        if (result.success) {
            showSuccess('addPlayerResult', 'Player added successfully!');
            document.getElementById('newPlayerName').value = '';
            document.getElementById('newPlayerEmail').value = '';
            
            // Reload players
            setTimeout(() => {
                loadPlayers();
                toggleAddPlayer();
            }, 1500);
        }
    } catch (error) {
        console.error('Error adding player:', error);
        showError('addPlayerResult', 'Error adding player: ' + error.message);
    }
}

// Utility functions
function showError(elementId, message) {
    document.getElementById(elementId).innerHTML = `<div class="error">❌ ${message}</div>`;
}

function showSuccess(elementId, message) {
    document.getElementById(elementId).innerHTML = `<div class="success">✅ ${message}</div>`;
}

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Only handle shortcuts when game is active and not in input fields
    if (!currentGameId || e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') {
        return;
    }
    
    switch(e.key) {
        case '1':
        case 'g':
        case 'G':
            recordShot('good');
            break;
        case '2':
        case 'o':
        case 'O':
            recordShot('ok');
            break;
        case '3':
        case 'b':
        case 'B':
            recordShot('bad');
            break;
        case 's':
        case 'S':
            if (player2Id) switchPlayer();
            break;
        case 'Escape':
            if (document.getElementById('shotDetails').classList.contains('show')) {
                cancelShot();
            }
            break;
        case 'Enter':
            if (document.getElementById('shotDetails').classList.contains('show')) {
                confirmShot();
            }
            break;
    }
});

// Auto-save game state periodically
setInterval(() => {
    if (currentGameId) {
        localStorage.setItem('snooker_game_stats', JSON.stringify({
            gameId: currentGameId,
            stats: gameStats,
            timestamp: Date.now()
        }));
    }
}, 30000); // Save every 30 seconds