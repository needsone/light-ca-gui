</div>
    </main>
    
    <footer class="footer">
        <div class="container">
            <p>
                &copy; <?php echo date('Y'); ?> SSL Certificate Manager | 
                Powered by <a href="https://smallstep.com/docs/step-ca" target="_blank">step-ca</a>
            </p>
            <?php if (defined('AD_ENABLED') && AD_ENABLED): ?>
            <p class="ad-info">
                <i class="fas fa-shield-alt"></i> Active Directory Authentication Enabled
            </p>
            <?php endif; ?>
        </div>
    </footer>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
