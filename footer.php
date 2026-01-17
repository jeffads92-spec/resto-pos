</div> <!-- Close main container if needed -->
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Helper functions
    function showLoading() {
        if (typeof document.getElementById('loadingSpinner') !== 'undefined') {
            document.getElementById('loadingSpinner').classList.add('active');
        }
    }

    function hideLoading() {
        if (typeof document.getElementById('loadingSpinner') !== 'undefined') {
            document.getElementById('loadingSpinner').classList.remove('active');
        }
    }
    
    // Format currency
    function formatRupiah(amount) {
        return 'Rp ' + parseInt(amount).toLocaleString('id-ID');
    }
    
    // Auto-hide alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 3000);
    </script>
</body>
</html>
