/**
 * 总首页 JavaScript
 */

;(function () {
    'use strict';

    // ========================================
    // Banner 轮播
    // ========================================
    class BannerSlider {
        constructor() {
            this.slider = document.querySelector('.home-banner-slider');
            if (!this.slider) return;

            this.slides = this.slider.querySelectorAll('.home-banner-slide');
            this.dots = document.querySelectorAll('.home-banner-dot');
            this.currentIndex = 0;
            this.autoplayInterval = null;
            this.autoplayDelay = 5000;

            this.init();
        }

        init() {
            if (this.slides.length <= 1) return;

            // 点击切换
            this.dots.forEach((dot, index) => {
                dot.addEventListener('click', () => this.goTo(index));
            });

            // 自动播放
            this.startAutoplay();

            // 鼠标悬停暂停
            this.slider.addEventListener('mouseenter', () => this.stopAutoplay());
            this.slider.addEventListener('mouseleave', () => this.startAutoplay());

            // 触摸滑动支持
            this.initTouchEvents();
        }

        goTo(index) {
            // 移除当前激活状态
            this.slides[this.currentIndex].classList.remove('active');
            this.dots[this.currentIndex]?.classList.remove('active');

            // 更新索引
            this.currentIndex = index;

            // 添加新激活状态
            this.slides[this.currentIndex].classList.add('active');
            this.dots[this.currentIndex]?.classList.add('active');
        }

        next() {
            const nextIndex = (this.currentIndex + 1) % this.slides.length;
            this.goTo(nextIndex);
        }

        startAutoplay() {
            if (this.autoplayInterval) return;
            this.autoplayInterval = setInterval(() => this.next(), this.autoplayDelay);
        }

        stopAutoplay() {
            if (this.autoplayInterval) {
                clearInterval(this.autoplayInterval);
                this.autoplayInterval = null;
            }
        }

        initTouchEvents() {
            let startX = 0;
            let endX = 0;

            this.slider.addEventListener('touchstart', (e) => {
                startX = e.touches[0].clientX;
            }, { passive: true });

            this.slider.addEventListener('touchend', (e) => {
                endX = e.changedTouches[0].clientX;
                const diff = startX - endX;

                if (Math.abs(diff) > 50) {
                    if (diff > 0) {
                        this.next();
                    } else {
                        this.goTo(this.currentIndex === 0 ? this.slides.length - 1 : this.currentIndex - 1);
                    }
                }
            }, { passive: true });
        }
    }

    // ========================================
    // 排行榜 Tab 切换
    // ========================================
    class RankTabs {
        constructor() {
            this.containers = document.querySelectorAll('.home-rank-section');
            this.init();
        }

        init() {
            this.containers.forEach(container => {
                const tabs = container.querySelectorAll('.rank-tab');
                const lists = container.querySelectorAll('.rank-list');
                if (tabs.length === 0 || lists.length === 0) return;

                tabs.forEach((tab, index) => {
                    tab.addEventListener('click', () => {
                        tabs.forEach(t => t.classList.remove('active'));
                        lists.forEach(l => l.classList.remove('active'));
                        
                        tab.classList.add('active');
                        lists[index]?.classList.add('active');
                    });
                });
            });
        }
    }

    // ========================================
    // 滚动动画
    // ========================================
    class ScrollAnimation {
        constructor() {
            this.elements = document.querySelectorAll('.home-section');
            this.init();
        }

        init() {
            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('visible');
                            observer.unobserve(entry.target);
                        }
                    });
                }, {
                    threshold: 0.1,
                    rootMargin: '0px 0px -50px 0px'
                });

                this.elements.forEach(el => observer.observe(el));
            } else {
                // 降级处理：直接显示
                this.elements.forEach(el => el.classList.add('visible'));
            }
        }
    }

    // ========================================
    // 搜索功能
    // ========================================
    const SearchBox = {
        input: null,
        form: null,

        init() {
            this.form = document.querySelector('.home-search-form');
            this.input = document.querySelector('.home-search-input');
            
            if (!this.form || !this.input) return;

            this.form.addEventListener('submit', (e) => {
                e.preventDefault();
                const keyword = this.input.value.trim();
                if (keyword) {
                    window.location.href = `/comic/search?keyword=${encodeURIComponent(keyword)}`;
                }
            });
        },

        highlight(keyword) {
            // 搜索框回填
            if (this.input && keyword) {
                this.input.value = keyword;
            }
        }
    };

    // ========================================
    // 懒加载图片
    // ========================================
    const LazyLoad = {
        init() {
            if ('loading' in HTMLImageElement.prototype) {
                // 浏览器原生支持
                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    img.src = img.dataset.src || img.src;
                });
            } else {
                // 降级处理
                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            if (img.dataset.src) {
                                img.src = img.dataset.src;
                                img.removeAttribute('data-src');
                            }
                            observer.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[data-src]').forEach(img => {
                    observer.observe(img);
                });
            }
        }
    };

    // ========================================
    // 初始化
    // ========================================
    document.addEventListener('DOMContentLoaded', () => {
        new BannerSlider();
        new RankTabs();
        new ScrollAnimation();
        SearchBox.init();
        LazyLoad.init();

        // URL 参数处理
        const urlParams = new URLSearchParams(window.location.search);
        const keyword = urlParams.get('keyword');
        if (keyword) {
            SearchBox.highlight(keyword);
        }
    });

    // 暴露全局方法
    window.HomePage = {
        refreshRank: function(type) {
            // 可扩展：点击刷新排行榜
            console.log('刷新排行榜:', type);
        }
    };

})();
