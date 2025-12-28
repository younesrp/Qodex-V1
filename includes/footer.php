<?php
// includes/footer.php
?>

    <footer class="mt-auto py-4 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5>Qodex</h5>
                    <p>Plateforme de quiz sécurisée pour enseignants et étudiants.</p>
                </div>
                <div class="col-md-6 text-end">
                    <p>&copy; <?= date('Y') ?> Qodex. Tous droits réservés.</p>
                    <p>Conforme aux critères de sécurité Web</p>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validation côté client
        function validateForm(form) {
            const requiredFields = form.querySelectorAll('[required]');
            for (let field of requiredFields) {
                if (!field.value.trim()) {
                    alert('Veuillez remplir tous les champs obligatoires');
                    field.focus();
                    return false;
                }
            }
            return true;
        }
        
        // Protection contre la double soumission
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.tagName === 'FORM') {
                const submitBtn = form.querySelector('button[type="submit"]');
                if (submitBtn) {
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> En cours...';
                }
            }
        });
    </script>
</body>
</html>