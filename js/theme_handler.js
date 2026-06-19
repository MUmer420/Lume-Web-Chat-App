
document.addEventListener('DOMContentLoaded', () => {
    const themeToggleBtn = document.getElementById('theme-toggle');

    if (themeToggleBtn) {
        themeToggleBtn.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            
            // Update page instantly
            document.documentElement.setAttribute('data-theme', newTheme);
        
            document.cookie = `lume-theme=${newTheme}; max-age=${60*60*24*30}; path=/; SameSite=Strict`;
        });
    }
});
