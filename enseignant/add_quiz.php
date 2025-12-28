<?php
session_start();
require_once '../config/database.php';

// Vérifier que l'utilisateur est enseignant
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'enseignant') {
    header('Location: ../auth/login.php');
    exit;
}

// Récupérer les catégories
$stmt = $conn->query("SELECT id, name FROM categories ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

$error = '';
$success = '';

// Générer token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Token de sécurité invalide.";
    } else {
        $title = trim(htmlspecialchars($_POST['title']));
        $description = trim(htmlspecialchars($_POST['description'] ?? ''));
        $category_id = (int)$_POST['category_id'];
        $teacher_id = $_SESSION['user_id'];
        
        // Validation du quiz
        if (empty($title)) {
            $error = "Le titre du quiz est obligatoire.";
        } elseif ($category_id <= 0) {
            $error = "Veuillez sélectionner une catégorie.";
        } else {
            // Vérifier que la catégorie existe
            $stmt = $conn->prepare("SELECT id FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            
            if (!$stmt->fetch()) {
                $error = "La catégorie sélectionnée n'existe pas.";
            } else {
                // Commencer une transaction
                $conn->beginTransaction();
                
                try {
                    // Créer le quiz
                    $stmt = $conn->prepare("INSERT INTO quizzes (title, description, category_id, teacher_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$title, $description, $category_id, $teacher_id]);
                    $quiz_id = $conn->lastInsertId();
                    
                    // Traiter les questions si elles existent
                    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
                        $question_index = 0;
                        
                        foreach ($_POST['questions'] as $question_data) {
                            $question_text = trim(htmlspecialchars($question_data['text']));
                            $question_type = $question_data['type'];
                            $points = (int)$question_data['points'];
                            
                            if (!empty($question_text) && $points > 0) {
                                // Insérer la question
                                $stmt = $conn->prepare("INSERT INTO questions (quiz_id, question_text, question_type, points) VALUES (?, ?, ?, ?)");
                                $stmt->execute([$quiz_id, $question_text, $question_type, $points]);
                                $question_id = $conn->lastInsertId();
                                
                                // Traiter les réponses
                                if (isset($question_data['answers']) && is_array($question_data['answers'])) {
                                    $answer_index = 0;
                                    
                                    foreach ($question_data['answers'] as $answer_text) {
                                        $answer_text = trim(htmlspecialchars($answer_text));
                                        
                                        if (!empty($answer_text)) {
                                            // Déterminer si c'est la bonne réponse (première réponse par défaut pour single, toutes pour multiple)
                                            $is_correct = 0;
                                            if ($question_type === 'single') {
                                                // Pour choix unique, seule la première réponse est correcte
                                                $is_correct = ($answer_index === 0) ? 1 : 0;
                                            } else {
                                                // Pour choix multiple, toutes les réponses sont correctes
                                                $is_correct = 1;
                                            }
                                            
                                            $stmt = $conn->prepare("INSERT INTO answers (question_id, answer_text, is_correct) VALUES (?, ?, ?)");
                                            $stmt->execute([$question_id, $answer_text, $is_correct]);
                                        }
                                        $answer_index++;
                                    }
                                }
                            }
                            $question_index++;
                        }
                        
                        if ($question_index === 0) {
                            throw new Exception("Vous devez ajouter au moins une question valide.");
                        }
                    } else {
                        throw new Exception("Vous devez ajouter au moins une question.");
                    }
                    
                    $conn->commit();
                    $success = "Quiz créé avec succès !";
                    
                    // Rediriger vers la gestion des quiz
                    header('Location: manage_quizzes.php?success=1');
                    exit();
                    
                } catch (Exception $e) {
                    $conn->rollBack();
                    $error = "Erreur : " . $e->getMessage();
                }
            }
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><i class="bi bi-plus-circle"></i> Créer un Quiz</h1>
        <a href="dashboard.php" class="btn btn-outline-primary">
            <i class="bi bi-arrow-left"></i> Retour
        </a>
    </div>
    
    <?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>
    
    <form method="POST" action="" id="quizForm">
        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        
        <!-- Informations du quiz -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-info-circle"></i> Informations du quiz</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label for="title" class="form-label">Titre du quiz *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               required maxlength="200" placeholder="Titre du quiz">
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="category_id" class="form-label">Catégorie *</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="" selected disabled>Sélectionnez une catégorie</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" 
                              rows="3" placeholder="Description du quiz"></textarea>
                </div>
            </div>
        </div>
        
        <!-- Questions -->
        <div class="card mb-4">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-question-circle"></i> Questions</h5>
                <button type="button" id="addQuestion" class="btn btn-light btn-sm">
                    <i class="bi bi-plus-lg"></i> Ajouter une question
                </button>
            </div>
            <div class="card-body">
                <div id="questionsContainer">
                    <!-- La première question sera ajoutée ici par JavaScript -->
                </div>
                
                <div class="text-center mt-3">
                    <button type="button" id="addQuestionBottom" class="btn btn-success">
                        <i class="bi bi-plus-lg"></i> Ajouter une autre question
                    </button>
                </div>
            </div>
        </div>
        
        <div class="text-center">
            <button type="submit" class="btn btn-primary btn-lg px-5 mb-4">
                <i class="bi bi-save"></i> Créer le quiz
            </button>
        </div>
    </form>
</div>

<script>
// Variables globales
let questionCounter = 0;
let answerCounters = {};

// Fonction pour générer un ID unique
function generateId() {
    return 'id_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
}

// Fonction pour créer une nouvelle question
function createQuestion() {
    questionCounter++;
    const questionId = generateId();
    answerCounters[questionId] = 2;
    
    const questionHTML = `
        <div class="question-container card mb-4" id="question_${questionId}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-question-lg"></i> Question ${questionCounter}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-question" 
                        onclick="removeQuestion('${questionId}')" ${questionCounter === 1 ? 'disabled' : ''}>
                    <i class="bi bi-trash"></i> Supprimer
                </button>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-8">
                        <label class="form-label">Texte de la question *</label>
                        <textarea class="form-control question-text" name="questions[${questionId}][text]" 
                                  rows="2" required placeholder="Entrez la question ici..."></textarea>
                    </div>
                    
                    <div class="col-md-4">
                        <label class="form-label">Type de question *</label>
                        <select class="form-select question-type" name="questions[${questionId}][type]" required onchange="updateAnswerType('${questionId}', this.value)">
                            <option value="single" selected>Choix unique</option>
                            <option value="multiple">Choix multiple</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Points *</label>
                        <input type="number" class="form-control question-points" 
                               name="questions[${questionId}][points]" min="1" max="10" value="1" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de réponses</label>
                        <div class="input-group">
                            <input type="number" class="form-control" value="2" min="2" max="6" id="answerCount_${questionId}" readonly>
                            <button type="button" class="btn btn-outline-success" onclick="addAnswer('${questionId}')">
                                <i class="bi bi-plus"></i> Ajouter réponse
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Réponses -->
                <div class="answers-section">
                    <label class="form-label">Réponses * (cochez la/les bonnes réponses)</label>
                    <div id="answersContainer_${questionId}" class="answers-container">
                        <!-- Les réponses seront ajoutées ici -->
                    </div>
                </div>
            </div>
        </div>
    `;
    
    return { html: questionHTML, id: questionId };
}

// Fonction pour ajouter une réponse à une question
function addAnswer(questionId) {
    const answerIndex = answerCounters[questionId];
    
    if (answerIndex >= 6) {
        alert('Maximum 6 réponses par question');
        return;
    }
    
    answerCounters[questionId]++;
    document.getElementById(`answerCount_${questionId}`).value = answerCounters[questionId];
    
    const answerHTML = `
        <div class="answer-item mb-2" id="answer_${questionId}_${answerIndex}">
            <div class="input-group">
                <div class="input-group-text">
                    <input class="form-check-input mt-0 correct-answer" 
                           type="${document.querySelector(`#question_${questionId} .question-type`).value === 'single' ? 'radio' : 'checkbox'}" 
                           name="questions[${questionId}][correct_answers][]" 
                           value="${answerIndex}"
                           ${answerIndex === 0 ? 'checked' : ''}>
                </div>
                <input type="text" class="form-control answer-text" 
                       name="questions[${questionId}][answers][${answerIndex}]" 
                       placeholder="Réponse ${answerIndex + 1}" required>
                <button type="button" class="btn btn-outline-danger" 
                        onclick="removeAnswer('${questionId}', ${answerIndex})" ${answerIndex < 2 ? 'disabled' : ''}>
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    
    document.getElementById(`answersContainer_${questionId}`).insertAdjacentHTML('beforeend', answerHTML);
}

// Fonction pour supprimer une réponse
function removeAnswer(questionId, answerIndex) {
    if (answerCounters[questionId] <= 2) {
        alert('Une question doit avoir au moins 2 réponses');
        return;
    }
    
    const answerElement = document.getElementById(`answer_${questionId}_${answerIndex}`);
    if (answerElement) {
        answerElement.remove();
        answerCounters[questionId]--;
        document.getElementById(`answerCount_${questionId}`).value = answerCounters[questionId];
        
        // Renumérotation des réponses restantes
        const answersContainer = document.getElementById(`answersContainer_${questionId}`);
        const answerItems = answersContainer.querySelectorAll('.answer-item');
        
        answerItems.forEach((item, index) => {
            const inputs = item.querySelectorAll('input');
            inputs[0].setAttribute('name', `questions[${questionId}][correct_answers][]`);
            inputs[0].value = index;
            inputs[1].setAttribute('name', `questions[${questionId}][answers][${index}]`);
            inputs[1].placeholder = `Réponse ${index + 1}`;
            
            // Activer/désactiver le bouton de suppression
            const removeBtn = item.querySelector('.btn-outline-danger');
            removeBtn.disabled = index < 2;
            removeBtn.onclick = function() { removeAnswer(questionId, index); };
        });
    }
}

// Fonction pour mettre à jour le type de réponse
function updateAnswerType(questionId, type) {
    const answersContainer = document.getElementById(`answersContainer_${questionId}`);
    const inputs = answersContainer.querySelectorAll('.correct-answer');
    
    inputs.forEach(input => {
        input.type = type === 'single' ? 'radio' : 'checkbox';
        
        // Pour les choix uniques, ne garder que le premier coché
        if (type === 'single') {
            const radioName = `correct_answer_${questionId}`;
            input.name = radioName;
            if (input.value === '0') {
                input.checked = true;
            }
        } else {
            input.name = `questions[${questionId}][correct_answers][]`;
        }
    });
}

// Fonction pour supprimer une question
function removeQuestion(questionId) {
    const questionElement = document.getElementById(`question_${questionId}`);
    const allQuestions = document.querySelectorAll('.question-container');
    
    if (allQuestions.length <= 1) {
        alert('Vous devez avoir au moins une question');
        return;
    }
    
    questionElement.remove();
    delete answerCounters[questionId];
    
    // Renumérotation des questions restantes
    const remainingQuestions = document.querySelectorAll('.question-container');
    questionCounter = 0;
    
    remainingQuestions.forEach((question, index) => {
        questionCounter++;
        const header = question.querySelector('.card-header h6');
        header.innerHTML = `<i class="bi bi-question-lg"></i> Question ${questionCounter}`;
        
        // Activer/désactiver le bouton de suppression
        const removeBtn = question.querySelector('.remove-question');
        removeBtn.disabled = remainingQuestions.length === 1;
    });
}

// Initialisation du formulaire
document.addEventListener('DOMContentLoaded', function() {
    const questionsContainer = document.getElementById('questionsContainer');
    
    // Ajouter la première question
    const firstQuestion = createQuestion();
    questionsContainer.innerHTML = firstQuestion.html;
    
    // Ajouter 2 réponses par défaut à la première question
    addAnswer(firstQuestion.id);
    addAnswer(firstQuestion.id);
    
    // Gestion des boutons d'ajout de question
    document.getElementById('addQuestion').addEventListener('click', function() {
        addNewQuestion();
    });
    
    document.getElementById('addQuestionBottom').addEventListener('click', function() {
        addNewQuestion();
    });
});

// Fonction pour ajouter une nouvelle question
function addNewQuestion() {
    const newQuestion = createQuestion();
    const questionsContainer = document.getElementById('questionsContainer');
    questionsContainer.insertAdjacentHTML('beforeend', newQuestion.html);
    
    // Ajouter 2 réponses par défaut
    setTimeout(() => {
        addAnswer(newQuestion.id);
        addAnswer(newQuestion.id);
    }, 100);
}

// Validation du formulaire
document.getElementById('quizForm').addEventListener('submit', function(e) {
    let isValid = true;
    const errorMessages = [];
    
    // Vérifier le titre
    const title = document.getElementById('title').value.trim();
    if (!title) {
        isValid = false;
        errorMessages.push('Le titre du quiz est requis');
    }
    
    // Vérifier la catégorie
    const category = document.getElementById('category_id').value;
    if (!category) {
        isValid = false;
        errorMessages.push('Veuillez sélectionner une catégorie');
    }
    
    // Vérifier les questions
    const questions = document.querySelectorAll('.question-container');
    if (questions.length === 0) {
        isValid = false;
        errorMessages.push('Vous devez ajouter au moins une question');
    }
    
    // Vérifier chaque question
    questions.forEach((question, index) => {
        const questionText = question.querySelector('.question-text').value.trim();
        if (!questionText) {
            isValid = false;
            errorMessages.push(`La question ${index + 1} doit avoir un texte`);
        }
        
        const points = question.querySelector('.question-points').value;
        if (!points || points < 1) {
            isValid = false;
            errorMessages.push(`La question ${index + 1} doit avoir au moins 1 point`);
        }
        
        const answers = question.querySelectorAll('.answer-text');
        if (answers.length < 2) {
            isValid = false;
            errorMessages.push(`La question ${index + 1} doit avoir au moins 2 réponses`);
        }
        
        // Vérifier que toutes les réponses ont du texte
        answers.forEach((answer, answerIndex) => {
            if (!answer.value.trim()) {
                isValid = false;
                errorMessages.push(`La réponse ${answerIndex + 1} de la question ${index + 1} doit avoir un texte`);
            }
        });
    });
    
    if (!isValid) {
        e.preventDefault();
        alert('Veuillez corriger les erreurs suivantes :\n\n' + errorMessages.join('\n'));
    }
});
</script>

<?php include '../includes/footer.php'; ?>