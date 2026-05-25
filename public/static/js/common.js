// 飞鸟漫画 - 全局公共脚本
(function() {
    'use strict';

    // ==================== 主题管理器 ====================
    window.ThemeManager = {
        STORAGE_KEY: 'feiniao-theme',
        DARK_CLASS: 'dark-theme',

        init() {
            this.applyTheme();
            this.bindEvents();
        },

        getStoredTheme() {
            try {
                return localStorage.getItem(this.STORAGE_KEY);
            } catch (e) {
                return null;
            }
        },

        setStoredTheme(theme) {
            try {
                localStorage.setItem(this.STORAGE_KEY, theme);
            } catch (e) {
                // 忽略存储错误
            }
        },

        applyTheme() {
            const stored = this.getStoredTheme();
            const isDark = stored === 'dark';
            
            // 同步到 html 和 body 元素
            if (isDark) {
                document.documentElement.classList.add(this.DARK_CLASS);
                document.body.classList.add(this.DARK_CLASS);
            } else {
                document.documentElement.classList.remove(this.DARK_CLASS);
                document.body.classList.remove(this.DARK_CLASS);
            }
            
            this.updateToggleIcon(isDark);
        },

        toggle() {
            const isDark = !document.documentElement.classList.contains(this.DARK_CLASS);
            
            if (isDark) {
                document.documentElement.classList.add(this.DARK_CLASS);
                document.body.classList.add(this.DARK_CLASS);
            } else {
                document.documentElement.classList.remove(this.DARK_CLASS);
                document.body.classList.remove(this.DARK_CLASS);
            }
            
            this.setStoredTheme(isDark ? 'dark' : 'light');
            this.updateToggleIcon(isDark);
            this.updateBackToTopTheme(isDark);
        },

        updateToggleIcon(isDark) {
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                const icon = themeToggle.querySelector('i');
                if (icon) {
                    icon.className = isDark ? 'fas fa-moon' : 'fas fa-sun';
                }
            }
        },

        updateBackToTopTheme(isDark) {
            const backToTop = document.querySelector('.back-to-top');
            if (backToTop) {
                if (isDark) {
                    backToTop.style.background = 'rgba(44, 44, 44, 0.9)';
                    backToTop.style.color = '#ffffff';
                    backToTop.style.borderColor = 'rgba(255, 255, 255, 0.08)';
                } else {
                    backToTop.style.background = 'rgba(255, 255, 255, 0.9)';
                    backToTop.style.color = '#1a1a1a';
                    backToTop.style.borderColor = 'rgba(0, 0, 0, 0.08)';
                }
            }
        },

        bindEvents() {
            const themeToggle = document.getElementById('themeToggle');
            if (themeToggle) {
                themeToggle.addEventListener('click', () => this.toggle());
            }
        }
    };

    // ==================== 导航菜单管理器 ====================
    window.NavManager = {
        init() {
            this.bindEvents();
        },

        bindEvents() {
            const navToggle = document.querySelector('.nav-toggle');
            const navMenu = document.querySelector('.nav-menu');
            const navLinks = document.querySelectorAll('.nav-link');

            // 移动端菜单切换
            if (navToggle && navMenu) {
                navToggle.addEventListener('click', () => {
                    navMenu.classList.toggle('active');
                    const icon = navToggle.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('fa-bars');
                        icon.classList.toggle('fa-times');
                    }
                });

                // 点击菜单项后关闭移动端菜单
                navLinks.forEach(link => {
                    link.addEventListener('click', () => {
                        navMenu.classList.remove('active');
                        const icon = navToggle.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-times');
                            icon.classList.add('fa-bars');
                        }
                    });
                });
            }

            // 平滑滚动
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        const offset = 60; // 导航栏高度
                        const elementPosition = target.getBoundingClientRect().top;
                        const offsetPosition = elementPosition + window.pageYOffset - offset;

                        window.scrollTo({
                            top: offsetPosition,
                            behavior: 'smooth'
                        });
                    }
                });
            });

            // 响应式菜单优化
            const handleResize = this.debounce(() => {
                if (window.innerWidth > 768 && navMenu) {
                    navMenu.classList.remove('active');
                    const icon = navToggle?.querySelector('i');
                    if (icon) {
                        icon.classList.remove('fa-times');
                        icon.classList.add('fa-bars');
                    }
                }
            }, 250);

            window.addEventListener('resize', handleResize);
        },

        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
    };

    // ==================== 返回顶部按钮 ====================
    window.BackToTop = {
        init() {
            this.createButton();
            this.bindEvents();
        },

        createButton() {
            const backToTop = document.createElement('button');
            backToTop.className = 'back-to-top';
            backToTop.innerHTML = '<i class="fas fa-arrow-up"></i>';
            
            // 根据当前主题设置初始样式
            const isDarkMode = document.documentElement.classList.contains('dark-theme');
            const backToTopBg = isDarkMode ? 'rgba(44, 44, 44, 0.9)' : 'rgba(255, 255, 255, 0.9)';
            const backToTopColor = isDarkMode ? '#ffffff' : '#1a1a1a';
            const backToTopBorder = isDarkMode ? 'rgba(255, 255, 255, 0.08)' : 'rgba(0, 0, 0, 0.08)';
            
            backToTop.style.cssText = `
                position: fixed;
                bottom: 24px;
                right: 24px;
                width: 44px;
                height: 44px;
                background: ${backToTopBg};
                backdrop-filter: blur(10px);
                color: ${backToTopColor};
                border: 1px solid ${backToTopBorder};
                border-radius: 8px;
                cursor: pointer;
                opacity: 0;
                visibility: hidden;
                transition: all 0.2s ease;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
                font-size: 16px;
                z-index: 999;
                display: flex;
                align-items: center;
                justify-content: center;
            `;

            document.body.appendChild(backToTop);
        },

        bindEvents() {
            const backToTop = document.querySelector('.back-to-top');
            if (!backToTop) return;

            window.addEventListener('scroll', () => {
                if (window.pageYOffset > 400) {
                    backToTop.style.opacity = '1';
                    backToTop.style.visibility = 'visible';
                } else {
                    backToTop.style.opacity = '0';
                    backToTop.style.visibility = 'hidden';
                }
            });

            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });

            // 添加悬停效果
            backToTop.addEventListener('mouseenter', () => {
                backToTop.style.transform = 'translateY(-2px)';
                backToTop.style.boxShadow = '0 8px 24px rgba(0, 0, 0, 0.15)';
            });

            backToTop.addEventListener('mouseleave', () => {
                backToTop.style.transform = '';
                backToTop.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.1)';
            });
        }
    };

    // ==================== 图片懒加载 ====================
    window.LazyLoader = {
        init() {
            if (!('IntersectionObserver' in window)) return;

            const imgObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        if (img.dataset.src) {
                            img.src = img.dataset.src;
                            img.removeAttribute('data-src');
                        }
                        img.classList.add('loaded');
                        imgObserver.unobserve(img);
                    }
                });
            }, {
                rootMargin: '50px'
            });

            document.querySelectorAll('img[data-src]').forEach(img => {
                imgObserver.observe(img);
            });
        },

        // 手动刷新懒加载（用于动态加载的内容）
        refresh() {
            this.init();
        }
    };

    // ==================== 视口动画 ====================
    window.ScrollAnimation = {
        init(selector = '.animate-on-scroll') {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: '0px 0px -50px 0px'
            };

            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('in-view');
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);

            // 观察需要动画的元素
            document.querySelectorAll(selector).forEach(el => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                observer.observe(el);
            });

            // 添加 CSS 类用于动画
            if (!document.getElementById('scroll-animation-style')) {
                const style = document.createElement('style');
                style.id = 'scroll-animation-style';
                style.textContent = `
                    .in-view {
                        opacity: 1 !important;
                        transform: translateY(0) !important;
                    }
                `;
                document.head.appendChild(style);
            }
        }
    };

    // ==================== API请求模块（自动处理Token） ====================
    window.ApiClient = {
        TOKEN_KEY: 'token',

        // 获取存储的token
        getToken() {
            try {
                return localStorage.getItem(this.TOKEN_KEY);
            } catch (e) {
                return null;
            }
        },

        // 保存token
        saveToken(token) {
            try {
                localStorage.setItem(this.TOKEN_KEY, token);
            } catch (e) {
                console.warn('保存token失败:', e);
            }
        },

        // 获取匿名token
        async getAnonymousToken() {
            try {
                const response = await fetch('/api/system/gettoken', {
                    headers: { 'Content-Type': 'application/json' }
                });
                const result = await response.json();

                if (result.code === 200 && result.data?.access_token) {
                    this.saveToken(result.data.access_token);
                    return result.data.access_token;
                }
                throw new Error(result.message || '获取token失败');
            } catch (error) {
                console.error('获取匿名token失败:', error);
                throw error;
            }
        },

        // 发送API请求（自动处理token）
        async request(url, options = {}) {
            let token = this.getToken();

            // 没有token时，自动获取匿名token
            if (!token) {
                token = await this.getAnonymousToken();
            }

            // 合并headers
            const headers = {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${token}`,
                ...options.headers
            };

            // 发送请求
            let response = await fetch(url, { ...options, headers });
            // 处理400+错误（token无效），重新获取匿名token并重试
            if (response.status >= 400) {                
                token = await this.getAnonymousToken();
                headers.Authorization = `Bearer ${token}`;
                response = await fetch(url, { ...options, headers });
            }

            return response;
        },

        // GET请求
        async get(url, params = {}) {
            const queryString = new URLSearchParams(params).toString();
            const fullUrl = queryString ? `${url}?${queryString}` : url;
            const response = await this.request(fullUrl, { method: 'GET' });
            const result = await response.json();
            // 401 时清除用户信息
            if (result.code === 401) {
                localStorage.removeItem('userInfo');
            }
            return result;
        },

        // POST请求
        async post(url, data = {}) {
            const response = await this.request(url, {
                method: 'POST',
                body: JSON.stringify(data)
            });
            const result = await response.json();
            // 401 时清除用户信息
            if (result.code === 401) {
                localStorage.removeItem('userInfo');
            }
            return result;
        },

        // 初始化（页面加载时自动获取token）
        async init() {
            const token = this.getToken();
            if (!token) {
                await this.getAnonymousToken();
            }
        }
    };

    // ==================== 工具函数 ====================
    window.Utils = {
        // 防抖函数
        debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        // 节流函数
        throttle(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        // 格式化日期
        formatDate(date, format = 'YYYY-MM-DD') {
            const d = new Date(date);
            const year = d.getFullYear();
            const month = String(d.getMonth() + 1).padStart(2, '0');
            const day = String(d.getDate()).padStart(2, '0');
            
            return format
                .replace('YYYY', year)
                .replace('MM', month)
                .replace('DD', day);
        },

        // 复制到剪贴板
        async copyToClipboard(text) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (err) {
                // 降级方案
                const textarea = document.createElement('textarea');
                textarea.value = text;
                textarea.style.position = 'fixed';
                textarea.style.opacity = '0';
                document.body.appendChild(textarea);
                textarea.select();
                const success = document.execCommand('copy');
                document.body.removeChild(textarea);
                return success;
            }
        },

        // 显示提示消息
        showToast(message, type = 'info', duration = 3000) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            toast.style.cssText = `
                position: fixed;
                top: 50%;
                left: 50%;
                transform: translate(-50%, -50%);
                padding: 12px 24px;
                background: var(--color-bg-color);
                color: var(--color-text-color);
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                z-index: 10000;
                font-size: 14px;
                opacity: 0;
                transition: opacity 0.3s ease;
            `;

            document.body.appendChild(toast);

            // 显示动画
            requestAnimationFrame(() => {
                toast.style.opacity = '1';
            });

            // 自动隐藏
            setTimeout(() => {
                toast.style.opacity = '0';
                setTimeout(() => {
                    document.body.removeChild(toast);
                }, 300);
            }, duration);
        }
    };

    // ==================== 页面加载完成后初始化 ====================
    document.addEventListener('DOMContentLoaded', async () => {
        // 初始化主题
        ThemeManager.init();

        // 初始化导航
        NavManager.init();

        // 初始化返回顶部按钮
        BackToTop.init();

        // 初始化图片懒加载
        LazyLoader.init();

        // 初始化滚动动画
        ScrollAnimation.init('.feature-card, .download-card, .stat-card, .payment-card, .animate-on-scroll');

        // 初始化API客户端（自动获取token）
        await ApiClient.init();
    });

    // 页面完全加载
    window.addEventListener('load', () => {
        document.body.classList.add('loaded');
    });

    // ==================== 弹层组件 ====================
    window.Modal = {
        container: null,
        currentModal: null,

        init() {
            this.container = document.getElementById('modalContainer');
        },

        // 生成唯一ID
        generateId() {
            return 'modal_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        },

        // 创建遮罩层
        createOverlay() {
            const overlay = document.createElement('div');
            overlay.className = 'modal-overlay';
            return overlay;
        },

        // 创建弹窗
        createModal(options = {}) {
            const {
                id = this.generateId(),
                title = '',
                content = '',
                showClose = true,
                showCancel = true,
                showConfirm = true,
                cancelText = '取消',
                confirmText = '确定',
                confirmDanger = false,
                onConfirm = null,
                onCancel = null,
                onClose = null,
                width = '',
                footerLeft = false
            } = options;

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.id = id;
            if (width) {
                modal.style.maxWidth = width;
            }

            // 头部
            let headerHtml = '';
            if (title || showClose) {
                headerHtml = `
                    <div class="modal-header">
                        ${title ? `<h3 class="modal-title">${title}</h3>` : '<div></div>'}
                        ${showClose ? '<button class="modal-close" data-close><i class="fas fa-times"></i></button>' : ''}
                    </div>
                `;
            }

            // 底部按钮
            let footerHtml = '';
            if (showCancel || showConfirm) {
                footerHtml = `
                    <div class="modal-footer ${footerLeft ? 'modal-footer-left' : ''}">
                        ${showCancel ? `<button class="modal-btn modal-btn-cancel" data-cancel>${cancelText}</button>` : ''}
                        ${showConfirm ? `<button class="modal-btn ${confirmDanger ? 'modal-btn-danger' : 'modal-btn-confirm'}" data-confirm>${confirmText}</button>` : ''}
                    </div>
                `;
            }

            modal.innerHTML = `
                ${headerHtml}
                <div class="modal-body">${content}</div>
                ${footerHtml}
            `;

            return modal;
        },

        // 显示弹窗
        show(options = {}) {
            let {
                type = 'alert', // alert, confirm, popup, loading, prompt
                title = '',
                message = '',
                content = '',
                showClose = true,
                showCancel = true,
                showConfirm = true,
                cancelText = '取消',
                confirmText = '确定',
                confirmDanger = false,
                confirmTextLeft = false,
                inputPlaceholder = '',
                inputValue = '',
                inputType = 'text',
                onConfirm = null,
                onCancel = null,
                onClose = null,
                width = '',
                closeOnConfirm = true,
                closeOnCancel = true,
                closeOnOverlay = true,
                showLoading = false,
                loadingText = '加载中...'
            } = options;

            // 关闭已有弹窗
            this.hide();

            const overlay = this.createOverlay();
            let modalContent = content;

            // 根据类型生成内容
            if (type === 'alert') {
                modalContent = `
                    <div class="modal-content-center">
                        <i class="modal-icon fas fa-info-circle" style="color: var(--color-primary)"></i>
                        <p class="modal-message">${message}</p>
                    </div>
                `;
                showCancel = false;
            } else if (type === 'confirm') {
                modalContent = `
                    <div class="modal-content-center">
                        <i class="modal-icon fas fa-question-circle" style="color: var(--color-warning)"></i>
                        <p class="modal-message">${message}</p>
                    </div>
                `;
            } else if (type === 'success') {
                modalContent = `
                    <div class="modal-content-center">
                        <i class="modal-icon fas fa-check-circle" style="color: var(--color-success)"></i>
                        <p class="modal-message">${message}</p>
                    </div>
                `;
                showCancel = false;
            } else if (type === 'error') {
                modalContent = `
                    <div class="modal-content-center">
                        <i class="modal-icon fas fa-times-circle" style="color: var(--color-danger)"></i>
                        <p class="modal-message">${message}</p>
                    </div>
                `;
                showCancel = false;
            } else if (type === 'prompt') {
                modalContent = `
                    <div class="modal-content-center">
                        <p class="modal-message">${message}</p>
                        <div class="modal-input-wrapper">
                            <input type="${inputType}" class="modal-input" placeholder="${inputPlaceholder}" value="${inputValue}" data-input>
                        </div>
                    </div>
                `;
            } else if (type === 'loading') {
                modalContent = `
                    <div class="modal-loading">
                        <div style="text-align: center">
                            <div class="modal-spinner"></div>
                            <p style="margin-top: 16px; color: var(--color-text-color-secondary)">${loadingText}</p>
                        </div>
                    </div>
                `;
                showClose = false;
                showCancel = false;
                showConfirm = false;
                closeOnOverlay = false;
            }
            // type 为 'popup' 或其他自定义类型时，modalContent 保持为传入的 content

            const modal = this.createModal({
                title,
                content: modalContent,
                showClose,
                showCancel,
                showConfirm,
                cancelText,
                confirmText,
                confirmDanger,
                footerLeft: confirmTextLeft,
                width
            });

            modal.classList.add(`modal-${type}`);

            overlay.appendChild(modal);
            this.container.appendChild(overlay);
            this.currentModal = { overlay, modal, options };

            // 触发动画
            requestAnimationFrame(() => {
                overlay.classList.add('show');
            });

            // 事件绑定
            const closeBtn = modal.querySelector('[data-close]');
            const cancelBtn = modal.querySelector('[data-cancel]');
            const confirmBtn = modal.querySelector('[data-confirm]');
            const inputEl = modal.querySelector('[data-input]');

            const closeHandler = (callback) => {
                return () => {
                    if (callback) {
                        const result = callback();
                        // 如果返回 Promise，等待完成后再关闭
                        if (result && typeof result.then === 'function') {
                            confirmBtn.disabled = true;
                            cancelBtn && (cancelBtn.disabled = true);
                            result.finally(() => {
                                confirmBtn.disabled = false;
                                cancelBtn && (cancelBtn.disabled = false);
                                if (result.value !== false) {
                                    this.hide();
                                }
                            });
                            return;
                        }
                        if (result === false) return;
                    }
                    this.hide();
                };
            };

            if (closeBtn) {
                closeBtn.addEventListener('click', closeHandler(onClose));
            }

            if (cancelBtn) {
                cancelBtn.addEventListener('click', closeHandler(onCancel));
            }

            if (confirmBtn) {
                confirmBtn.addEventListener('click', () => {
                    let inputValue = null;
                    if (inputEl) {
                        inputValue = inputEl.value;
                    }
                    if (onConfirm) {
                        const result = onConfirm(inputValue);
                        if (result && typeof result.then === 'function') {
                            confirmBtn.disabled = true;
                            cancelBtn && (cancelBtn.disabled = true);
                            result.finally(() => {
                                confirmBtn.disabled = false;
                                cancelBtn && (cancelBtn.disabled = false);
                                if (closeOnConfirm !== false && result.value !== false) {
                                    this.hide();
                                }
                            });
                        } else {
                            if (closeOnConfirm !== false && result !== false) {
                                this.hide();
                            }
                        }
                    } else {
                        this.hide();
                    }
                });
            }

            // 点击遮罩关闭
            if (closeOnOverlay) {
                overlay.addEventListener('click', (e) => {
                    if (e.target === overlay) {
                        closeHandler(onClose)();
                    }
                });
            }

            // ESC 键关闭
            const escHandler = (e) => {
                if (e.key === 'Escape') {
                    document.removeEventListener('keydown', escHandler);
                    closeHandler(onClose)();
                }
            };
            document.addEventListener('keydown', escHandler);

            // 自动聚焦输入框
            if (inputEl) {
                setTimeout(() => inputEl.focus(), 100);
            }

            return modal.id;
        },

        // 隐藏弹窗
        hide() {
            if (this.currentModal) {
                const { overlay } = this.currentModal;
                overlay.classList.remove('show');
                setTimeout(() => {
                    if (overlay.parentNode) {
                        overlay.parentNode.removeChild(overlay);
                    }
                }, 300);
                this.currentModal = null;
            }
        },

        // Alert 弹窗
        alert(message, onConfirm) {
            return this.show({
                type: 'alert',
                message,
                onConfirm: () => {
                    if (onConfirm) onConfirm();
                }
            });
        },

        // Success 弹窗
        success(message, onConfirm) {
            return this.show({
                type: 'success',
                message,
                onConfirm: () => {
                    if (onConfirm) onConfirm();
                }
            });
        },

        // Error 弹窗
        error(message, onConfirm) {
            return this.show({
                type: 'error',
                message,
                onConfirm: () => {
                    if (onConfirm) onConfirm();
                }
            });
        },

        // Confirm 确认弹窗
        confirm(message, onConfirm, onCancel) {
            return this.show({
                type: 'confirm',
                message,
                onConfirm: () => {
                    if (onConfirm) onConfirm();
                },
                onCancel: () => {
                    if (onCancel) onCancel();
                }
            });
        },

        // Prompt 输入弹窗
        prompt(message, onConfirm, placeholder = '', defaultValue = '') {
            return this.show({
                type: 'prompt',
                message,
                inputPlaceholder: placeholder,
                inputValue: defaultValue,
                onConfirm: (value) => {
                    if (onConfirm) return onConfirm(value);
                }
            });
        },

        // Popup 自定义弹窗
        popup(options = {}) {
            return this.show({
                type: 'popup',
                ...options
            });
        },

        // Loading 加载弹窗
        loading(text = '加载中...') {
            return this.show({
                type: 'loading',
                loadingText: text
            });
        },

        // 更新弹窗内容
        updateContent(content) {
            if (this.currentModal) {
                const body = this.currentModal.modal.querySelector('.modal-body');
                if (body) {
                    body.innerHTML = content;
                }
            }
        },

        // 更新弹窗标题
        updateTitle(title) {
            if (this.currentModal) {
                const titleEl = this.currentModal.modal.querySelector('.modal-title');
                if (titleEl) {
                    titleEl.textContent = title;
                }
            }
        }
    };

    // 初始化弹层
    Modal.init();

    // 控制台欢迎信息
    console.log('%c飞鸟漫画', 'color: #0078d4; font-size: 24px; font-weight: 600; font-family: "Segoe UI Variable", sans-serif;');
    console.log('%cWindows 11 风格设计', 'color: #5f5f5f; font-size: 14px;');
    console.log('%c开源项目，欢迎共建', 'color: #717171; font-size: 12px;');

})();
