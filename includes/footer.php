        </div> <!-- Fermeture du container -->
    </main>
    
    <footer class="py-4 mt-5 bg-dark text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h5 class="mb-3"><i class="bi bi-puzzle-fill"></i> Qodex</h5>
                    <p class="mb-0">Plateforme de quiz sécurisée pour enseignants et étudiants.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <p class="mb-1">&copy; <?= date('Y') ?> Qodex. Tous droits réservés.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Activer les dropdowns
        document.addEventListener('DOMContentLoaded', function() {
            var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
            var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        });
    </script>
</body>
</html>