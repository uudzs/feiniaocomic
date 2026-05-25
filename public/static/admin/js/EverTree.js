layui.use(['jquery'], function() {
    const $ = layui.$;
    //--------------------------------------------------
    // 常量声明
    //--------------------------------------------------
    const COOKIE_NAME = 'tree_state'; // 状态存储的Cookie名称

    //--------------------------------------------------
    // Cookie状态管理工具
    //--------------------------------------------------
    const treeState = {
        /**
         * 获取当前树状态
         * @returns {Object} 解析后的Cookie状态对象
         */
        get: () => {
            const cookie = document.cookie.match('(^|;) ?' + COOKIE_NAME + '=([^;]*)(;|$)');
            return cookie ? JSON.parse(cookie[2]) : {};
        },

        /**
         * 保存树状态到Cookie
         * @param {Object} state - 要存储的状态对象
         */
        set: (state) => {
            document.cookie = `${COOKIE_NAME}=${JSON.stringify(state)}; path=/; max-age=${7 * 24 * 3600}`;
        }
    };

    //--------------------------------------------------
    // DOM初始化相关方法
    //--------------------------------------------------
    
    // 初始化树节点缩进
    const initIndentation = () => {
        $('.c-tree-item[data-level]').each(function() {
            const $item = $(this);
            if (!$item.data('initialized')) {
                const level = $item.data('level');
                $item.css('margin-left', level * 20 + 'px')
                     .data('initialized', true);
            }
        });
    };

    //--------------------------------------------------
    // 节点关系查询方法
    //--------------------------------------------------
    
    // 获取直接子节点 
    const getDirectChildren = (pid) => {
        return $(`.c-tree-item[data-pid="${pid}"]`);
    };

    //--------------------------------------------------
    // 核心状态管理方法
    //--------------------------------------------------
    
    //  递归处理所有子节点
    const handleChildren = (pid, isCollapsed, animate) => {
        getDirectChildren(pid).each(function() {
            const $child = $(this);
            const childId = $child.data('id');
            
            // 更新节点状态
            $child.toggleClass('collapsed', isCollapsed)
                .find('.tree-switch')
                .removeClass('layui-icon-subtraction layui-icon-addition')
                .addClass(isCollapsed ? 'layui-icon-addition' : 'layui-icon-subtraction');

            // 动画控制
            if (animate) {
                $child.stop()[isCollapsed ? 'slideUp' : 'slideDown'](200);
            } else {
                $child.toggle(!isCollapsed);
            }

            handleChildren(childId, isCollapsed, animate);
        });
    };

    /**
     * 处理单个节点状态
     * @param {jQuery} $item - 节点元素
     * @param {boolean} isCollapsed - 是否折叠
     * @param {boolean} [animate=true] - 是否使用动画
     */
    const handleNodeState = ($item, isCollapsed, animate = true) => {
        const pid = $item.data('id');
        const state = treeState.get();

        // 更新当前节点
        $item.toggleClass('collapsed', isCollapsed)
            .find('.tree-switch')
            .removeClass('layui-icon-subtraction layui-icon-addition')
            .addClass(isCollapsed ? 'layui-icon-addition' : 'layui-icon-subtraction');

        // 递归更新状态树
        const updateState = (currentPid, collapsed) => {
            state[currentPid] = !collapsed;
            getDirectChildren(currentPid).each(function() {
                updateState($(this).data('id'), collapsed);
            });
        };

        updateState(pid, isCollapsed);
        treeState.set(state);

        handleChildren(pid, isCollapsed, animate);
    };

    //--------------------------------------------------
    // 状态初始化方法
    //--------------------------------------------------
    
    /**
     * 从Cookie恢复树状态
     */
    const initTreeState = () => {
        const state = treeState.get();
        Object.keys(state).forEach(pid => {
            const $item = $(`.c-tree-item[data-id="${pid}"]`);
            if ($item.length) {
                handleNodeState($item, !state[pid], false);
            }
        });
    };

    //--------------------------------------------------
    // 路径展开方法
    //--------------------------------------------------
    
    /**
     * 展开当前节点的所有祖先节点
     * @param {jQuery} $element - 当前节点元素
     */
    const expandAncestors = ($element) => {
        let currentPid = $element.data('pid');
        while (currentPid) {
            // 修改此行，将currentPid转为字符串
            const $parent = $(`.c-tree-item[data-id="${String(currentPid)}"]`);
            if ($parent.length && $parent.hasClass('collapsed')) {
                handleNodeState($parent, false, false);
                currentPid = $parent.data('pid');
            } else {
                currentPid = null;
            }
        }
    };

    //--------------------------------------------------
    // 事件绑定
    //--------------------------------------------------
    
    /**
     * 初始化树交互功能
     */
    const initTree = () => {
        $('.c-catetree').on('click', '.tree-switch', function() {
            const $item = $(this).closest('.c-tree-item');
            const isCollapsed = !$item.hasClass('collapsed');
            handleNodeState($item, isCollapsed);
        });
    };

    //--------------------------------------------------
    // 主初始化流程
    //--------------------------------------------------
    $(function() {
        initIndentation();    // 初始化缩进
        initTreeState();      // 恢复存储状态
        initTree();           // 绑定交互事件

        // 展开当前路径的祖先节点
        $('.tree-cur').each(function() {
            expandAncestors($(this));
        });
    });
});