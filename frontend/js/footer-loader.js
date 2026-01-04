
(function() {
    const footerContainer = document.getElementById('footer-container');
    
    if (footerContainer) {
    
        const currentPath = window.location.pathname;
        const isInPageFolder = currentPath.includes('/page/');
        const footerPath = isInPageFolder ? '../components/footer.html' : 'components/footer.html';
        
        fetch(footerPath)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Failed to load footer');
                }
                return response.text();
            })
            .then(html => {
                footerContainer.innerHTML = html;
            })
            .catch(error => {
                console.error('Error loading footer:', error);
   
                const altPath = isInPageFolder ? 'components/footer.html' : '../components/footer.html';
                fetch(altPath)
                    .then(response => {
                        if (response.ok) {
                            return response.text();
                        }
                        throw new Error('Failed to load footer');
                    })
                    .then(html => {
                        footerContainer.innerHTML = html;
                    })
                    .catch(err => {
                        console.error('Error loading footer with alternative path:', err);
                    });
            });
    }
})();

