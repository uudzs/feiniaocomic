(function() {
    'use strict';

    // 等待 DOM 加载完成
    document.addEventListener('DOMContentLoaded', () => {
        // 初始化首页特定的功能
        initComicCards();
    });

    // ==================== 漫画卡片交互 ====================
    function initComicCards() {
        const comicCards = document.querySelectorAll('.comic-card');
        
        comicCards.forEach(card => {
            // 添加悬停效果
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-4px)';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = '';
            });
        });
    }

})();
