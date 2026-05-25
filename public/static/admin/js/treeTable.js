// -----------------------------------------------------------------------------
// 版权所有 (c) 2025 quanyong
// 作者 quanyong <quanyoung.com>
// 版本 v1.2.2025
// -----------------------------------------------------------------------------

// -----------------------------------------------------------------------------
// TreeTable 组件 - 基于 Layui 的树形表格组件
// 提供树形结构数据的展示、展开/折叠、状态持久化等功能
// -----------------------------------------------------------------------------

layui.define(['table', 'jquery', 'layer'], function (exports) {
    'use strict';
    const $ = layui.$,
        table = layui.table,
        layer = layui.layer;

    // 树形表格构造函数
    const TreeTable = function (options) {
        // 合并默认配置和用户配置
        this.config = $.extend({
            elem: '#dataTable',        // 表格容器选择器
            url: '',                   // 数据接口地址
            toolbar: '#toolbar',       // 工具栏选择器
            model: '',                 // 数据模型名称
            SortUrl: '',               // 排序接口地址
            cols: [],                  // 表头配置
            expandKey: 'tree_expand_status', // 本地存储展开状态的键名
            tree: {                    // 树形结构配置
                iconColumn: 2,         // 展开图标所在列索引
                indentSize: 20         // 子级缩进像素
            }
        }, options);

        this.nodeMap = {};  // 节点映射表（用于快速查找节点）
        this.init();        // 初始化组件
    };

    // 原型对象
    TreeTable.prototype = {
        // ---------------------------------------------------------------------
        // 组件初始化
        // ---------------------------------------------------------------------
        init: function () {
            this.initEvent();   // 初始化事件监听
            this.loadData();    // 加载数据
        },

        // ---------------------------------------------------------------------
        // 初始化事件监听（工具栏按钮事件）
        // ---------------------------------------------------------------------
        initEvent: function () {
            const _this = this;

            // 监听工具栏点击事件
            table.on('toolbar(dataTable)', function (obj) {
                switch (obj.event) {
                    case 'expandAll':    // 展开全部
                        layer.msg('正在展开全部...', {icon: 16, shade: 0.01});
                        setTimeout(() => _this.expandAll(), 50);
                        break;
                    case 'collapseAll':  // 折叠全部
                        layer.msg('正在折叠全部...', {icon: 16, shade: 0.01});
                        setTimeout(() => _this.collapseAll(), 50);
                        break;  

                    case 'openform':// 打开表单弹窗
                    const currentElem = $(this); // 当前操作元素
                    const url = currentElem.data('url'); // 操作接口地址 
                         _this.openForm(currentElem.data('params'), url);
                        break;

                }
            });
        },

        // ---------------------------------------------------------------------
        // 展开全部节点
        // ---------------------------------------------------------------------
        expandAll: function () {
            layer.load(1, {shade: 0.2});
            // 遍历所有节点设置展开状态
            Object.values(this.nodeMap).forEach(node => {
                node.expanded = true;
                if (node.children.length) node._isLeaf = false;
            });
            this.saveExpandState();  // 保存展开状态
            this.renderTable();      // 重新渲染表格
            layer.msg('已展开全部节点', {icon: 1, time: 800});
            layer.closeAll('loading');
        },

        // ---------------------------------------------------------------------
        // 折叠全部节点
        // ---------------------------------------------------------------------
        collapseAll: function () {
            layer.load(1, {shade: 0.2});
            // 遍历所有节点设置折叠状态
            Object.values(this.nodeMap).forEach(node => {
                node.expanded = false;
            });
            this.saveExpandState();  // 保存展开状态
            this.renderTable();      // 重新渲染表格
            layer.msg('已折叠全部节点', {icon: 1, time: 800});
            layer.closeAll('loading');
        },


        // ---------------------------------------------------------------------
        // 表单弹窗控制器 
        // ---------------------------------------------------------------------
        openForm:function(params = {}, url) { 
            const options = {
                title: '操作信息',
                width: '1280px',
                height: '96%',
                ...params // 正确展开用户参数
            };

            layer.open({
                title: options.title, // 此时会正确显示 "添加位置"
                type: 2,
                shade: 0.8,
                content: url,
                area: [options.width, options.height]
            });
        },


        // ---------------------------------------------------------------------
        // 加载远程数据
        // ---------------------------------------------------------------------
        loadData: function () {
            const _this = this;
            layer.load(2, {shade: 0.1});

            $.ajax({
                url: this.config.url,
                type: 'GET',
                dataType: 'json',
                success: function (res) {
                    layer.closeAll('loading');
                    if (res.code === 0) {
                        _this.initTreeData(res.data);  // 初始化树形数据
                        _this.renderTable();           // 渲染表格
                    } else {
                        layer.msg(res.msg || '数据加载失败', {icon: 2});
                    }
                },
                error: function () {
                    layer.closeAll('loading');
                    layer.msg('请求失败，请检查网络', {icon: 2});
                }
            });
        },

        // ---------------------------------------------------------------------
        // 初始化树形数据结构
        // 参数 data: 原始平面数据
        // ---------------------------------------------------------------------
        initTreeData: function (data) {
            // 从本地存储读取展开状态
            const expandStates = JSON.parse(
                localStorage.getItem(this.config.expandKey) || "{}"
            );

            // 构建节点映射表
            this.nodeMap = {};
            data.forEach(node => {
                node.children = [];  // 初始化子节点数组
                this.nodeMap[node.id] = node;
            });

            // 构建父子层级关系
            data.forEach(node => {
                const parent = this.nodeMap[node.pid];
                parent && parent.children.push(node);
            });

            // 初始化节点状态
            Object.values(this.nodeMap).forEach(node => {
                node.hasChildren = node.children.length > 0;  // 是否有子节点
                node._isLeaf = !node.hasChildren;             // 是否为叶子节点
                node.expanded = !!expandStates[node.id];     // 展开状态
                // 子节点排序（按sort字段和id排序）
                node.children.sort((a, b) => a.sort - b.sort || a.id - b.id);
            });
        },

        // ---------------------------------------------------------------------
        // 保存节点展开状态到本地存储
        // ---------------------------------------------------------------------
        saveExpandState: function () {
            const states = {};
            Object.values(this.nodeMap).forEach(node => {
                states[node.id] = node.expanded;  // 记录每个节点的展开状态
            });
            localStorage.setItem(this.config.expandKey, JSON.stringify(states));
        },

        // ---------------------------------------------------------------------
        // 获取需要显示的表格数据（根据展开状态过滤）
        // ---------------------------------------------------------------------
        getDisplayData: function () {
            const displayData = [];
            // 获取根节点并按排序字段排序
            const rootNodes = Object.values(this.nodeMap)
                .filter(node => node.pid === 0)
                .sort((a, b) => a.sort - b.sort || a.id - b.id);

            // 递归遍历可见节点
            const traverse = (node) => {
                if (this.isVisible(node)) {
                    displayData.push(node);
                    if (node.expanded) {  // 如果节点是展开状态，继续遍历子节点
                        node.children.forEach(child => traverse(child));
                    }
                }
            };

            rootNodes.forEach(root => traverse(root));
            return displayData;
        },

        // ---------------------------------------------------------------------
        // 判断节点是否可见（父节点必须全部展开）
        // 参数 node: 要检查的节点
        // ---------------------------------------------------------------------
        isVisible: function (node) {
            if (node.pid === 0) return true;  // 根节点始终可见
            const parent = this.nodeMap[node.pid];
            return parent && parent.expanded && this.isVisible(parent); // 递归检查父节点
        },

        // ---------------------------------------------------------------------
        // 渲染表格
        // ---------------------------------------------------------------------
        renderTable: function () {
            const _this = this;
            // try { table.reload('dataTable'); } catch (e) {}  // 尝试先重载表格
            const instance = table.checkStatus('dataTable'); 
            table.render({
                id: 'dataTable',
                elem: this.config.elem,
                data: this.getDisplayData(),  // 获取过滤后的数据
                toolbar: this.config.toolbar,
                cols: this.config.cols,
                model: this.config.model,
                SortUrl: this.config.SortUrl,
                isTreeTable: true,      // 标记为树形表格
                height: 'full-130',     // 表格高度
                
                // 渲染完成回调
                done: function () {
                    // 更新展开/折叠按钮状态
                    $('.btn-expand').each(function () {
                        const id = $(this).data('id');
                        const node = _this.nodeMap[id];
                        $(this).html(node.expanded ? '-' : '+')
                            .toggleClass('layui-icon', node.expanded)
                            .toggleClass('layui-icon', !node.expanded);
                    });

                    // 绑定展开/折叠点击事件
                    $('.btn-expand').off('click').on('click', function () {
                        const id = $(this).data('id');
                        const node = _this.nodeMap[id];
                        node.expanded = !node.expanded;  // 切换状态
                        _this.saveExpandState();        // 保存状态
                        _this.renderTable();             // 重新渲染
                        // layer.msg(node.expanded ? '已展开' : '已折叠', {
                        //     // icon: 1,
                        //     time: 800
                        // });
                    });
                }
            });
        }
    };

    // 导出组件
    exports('treeTable', function (options) {
        return new TreeTable(options);
    });
});
// -----------------------------------------------------------------------------