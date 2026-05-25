layui.use(function () {
    // 第三方库组件初始化 
    var layer = layui.layer,
        form = layui.form,
        upload = layui.upload,
        table = layui.table,
        laydate = layui.laydate,
        element = layui.element,
        $ = layui.$;

    // 初始化左侧导航菜单
    element.render('nav');

    // 初始化时间选择器 
    laydate.render({
        elem: '#date-time'
    });

    // 表格事件处理器 
    table.on('toolbar(dataTable)', function (obj) {
        // 统一参数声明
        const tableId = 'dataTable'; // 表格实例ID
        const currentElem = $(this); // 当前操作元素
        const url = currentElem.data('url'); // 操作接口地址
        const checkStatus = table.checkStatus(tableId); // 表格选中状态
        const checkData = checkStatus.data; // 选中数据集合

        switch (obj.event) {
            // 刷新表格
            case 'refresh':
                table.reload(tableId);
                break;

            // 打开表单弹窗
            case 'openform':
                openForm(currentElem.data('params'), url);
                break;

            // 批量移动数据
            case 'moveAll':
                moveAll(table, form, checkData, url, '.c-tree-item');
                break;

            // 批量操作数据
            case 'batchAll':
                batchAll(table, checkData, url, {
                    emptyMsg: '请选择要还原的信息',
                    confirmTpl: '确定要还原选中的${num}篇信息吗？',
                    successMsg: '已成功还原'
                });
                break;

            // 批量删除数据
            case 'del':

                del({
                    isTreeTable: obj.config.isTreeTable,
                    url: url,
                    ids: checkData.map(item => item.id),
                    title: `确定删除选中的 ${checkData.length} 条数据吗？`
                });
                break;
        }
    });

    //  行工具事件处理
    table.on('tool(dataTable)', function (obj) {
        switch (obj.event) {
            case 'openform':
                const params = $(this).data('params') || {};
                const url = $(this).data('url');
                openForm(params, url);
                break;

            case 'del':
                const param = $(this).data('params') || {};
                const data = obj.data;
                // 优先使用params中的title，否则使用data.title
                const delTitle = param.title || `确定删除【${data.title}】吗？`;
                del({
                    isTreeTable: obj.config.isTreeTable,
                    url: $(this).data('url'),
                    ids: obj.data.id,
                    title: delTitle  // 使用处理后的标题
                });
                break;
        }
    });

    // 表格排序编辑服务
    table.on('edit(dataTable)', (obj) => {
        const { field, value, data } = obj;
        if (field !== 'sort') return;

        const { SortUrl: url, model, isTreeTable } = obj.config;
        const oldValue = data.sort;

        // 输入格式校验
        if (!/^\d+$/.test(value)) {
            layer.msg("排序必须为整数", { icon: 2 });
            return obj.update({ sort: oldValue });
        }

        const loadId = layer.msg('正在保存...', { icon: 16, shade: 0.1, time: 0 });

        $.post(url, {
            id: data.id,
            value,
            model,
            _token: $('meta[name="csrf-token"]').attr('content') // CSRF防护
        }).done(res => {
            layer.close(loadId);

            if (res.code !== 200) {
                layer.msg(res.msg || '保存失败', { icon: 0 });
                return obj.update({ sort: oldValue });
            }

            layer.msg(res.msg, { icon: 1, time: 1500 }, () => {
                isTreeTable
                    ? window.location.reload()
                    : table.reload('dataTable', { page: { curr: getCurrentPage() }, where: {} });
            });
        }).fail(() => {
            layer.close(loadId);
            layer.msg('请求异常', { icon: 0 });
            obj.update({ sort: oldValue });
        });
    });


    // 批量移动信息
    function moveAll(table, form, checkData, moveUrl, treeSelector) {
        if (checkData.length === 0) {
            layer.msg('请先选择要移动的信息', { icon: 3 });
            return false;
        }

        layer.open({
            type: 1,
            title: '批量移动信息',
            area: ['480px', '640px'],
            content: $('#moveDialogTpl').html(),
            success: function (layero, index) {
                form.render('select');

                form.on('submit(moveSubmit)', function (data) {
                    const ids = checkData.map(item => item.id);
                    const targetCateId = data.field.cateid;

                    $.post(moveUrl, {
                        ids: ids,
                        cateid: targetCateId,
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }).done(res => {
                        if (res.code === 200) {
                            table.reload('dataTable', { where: { cateid: targetCateId } });
                            $(treeSelector).removeClass('tree-cur')
                                .filter(`[data-id="${targetCateId}"]`).addClass('tree-cur');
                            layer.close(index);
                        }
                        layer.msg(res.msg || '操作完成');
                    });
                    return false;
                });
            }
        });
    }

    // 删除 
    function del(params) {
        // 参数标准化
        const rawIds = Array.isArray(params.ids) ? params.ids : [params.ids];

        // 严格过滤ID
        const validIds = [...new Set(
            rawIds.map(id => parseInt(id, 10))
                .filter(id => !isNaN(id) && id > 0)
        )];

        if (validIds.length === 0) {
            layer.msg('请选择有效数据', { icon: 3 });
            return;
        }

        // 确认提示
        const confirmMsg = params.title || `确定删除选中的 ${validIds.length} 条数据？`;
        layer.confirm(confirmMsg, {
            title: '删除确认',
            icon: 5,
            skin: 'layui-layer-red',
            btnAlign: 'c',
            closeBtn: 0,
            maxWidth: 400,
            btn: ['确认删除', '取消']
        }, () => {
            const loading = layer.msg('<i class="layui-icon layui-icon-loading"></i> 正在删除...', {
                icon: 16,
                shade: 0.3,
                time: 0
            });

            // 发送请求
            $.ajax({
                url: params.url,
                method: 'POST',
                dataType: 'json',
                data: {
                    ids: validIds.join(','),
                    _token: $('meta[name="csrf-token"]').attr('content')
                }
            }).done(res => {
                layer.close(loading);
                if (res.code === 200) {
                    layer.msg(res.msg || '删除成功!!!', { icon: 1 }, () => {
                        // 刷新策略
                        if (params.isTreeTable) {
                            window.location.reload();
                        } else {
                            const currPage = $('.layui-laypage-curr input').val() || 1;
                            layui.table.reload('dataTable', { page: { curr: currPage } });
                        }
                    });
                } else {
                    layer.confirm(res.msg || `操作失败 (CODE: ${res.code})`, {
                        icon: 2,
                        skin: 'layui-layer-red',
                        btnAlign: 'c',
                        closeBtn: 0,
                        maxWidth: 400,
                        btn: ['知道了', '取消'],
                        time: 18000

                    });
                }
            }).fail(xhr => {
                layer.close(loading);
                const status = xhr.status;
                let errorMsg = xhr.responseJSON?.msg || `服务器错误 (${status})`;
                if (status === 0) errorMsg = '网络连接失败';
                layer.msg(errorMsg, { icon: 2, time: 3000 });
            });
        });
    }



    // 获取当前分页页码
    function getCurrentPage() {
        return $('.layui-table-page')
            .find('.layui-laypage-curr')
            .text()
            .replace(/第|页/g, '');
    }

    // 状态切换处理器
    form.on('checkbox(changeStatus)', obj => {
        const $el = $(obj.elem);
        const { model, field, url, id } = $el.data();
        const loadIdx = layer.msg('状态更新中...', { icon: 16, shade: 0.3, time: 500 });

        $.ajax({
            type: "POST",
            url,
            dataType: "json",
            headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
            data: { model, field, id }
        }).done(res => {
            layer.msg(res.msg, { icon: res.code === 200 ? 1 : 5, time: 800 });
        }).fail(() => {
            layer.msg('服务连接超时', { icon: 2 });
        }).always(() => layer.close(loadIdx));
    });

    // 表单提交
    form.on('submit(c-verify)', data => {

        // 添加请求来源标识
        data.field.request_source = 'layui-form';

        $.ajax({
            url: data.form.action,
            type: 'POST',
            data: data.field,
            dataType: 'json',
        }).done(res => {

            // 解构响应数据
            const { code, msg, url, require_full_reload } = res;

            // 通用成功处理
            const handleSuccess = () => {
                // 优先检查强制刷新标记
                if (require_full_reload) {
                    parent.location.reload(true);  // 强制刷新父页
                    return;
                }

                // 常规跳转或局部刷新
                url ? parent.location.replace(url) : (
                    parent.layer.close(parent.layer.getFrameIndex(window.name)),
                    parent.layui.table?.reload('dataTable')
                );
            };

            // 错误处理
            const handleError = () => layer.msg(msg, {
                icon: 2,
                anim: 6,
                time: 2000
            });

            // 结果判断
            code === 200
                ? layer.msg(msg, { icon: 1, time: 2000 }, handleSuccess)
                : handleError();
        }).fail((xhr, status, error) => {
            layer.msg(`请求异常: ${status}`, {
                icon: 2,
                anim: 6,
                time: 2000
            });

        });

        return false;
    });

    // 文件上传处理器
    upload.render({
        elem: '.upload-file',
        url: $('#uploadUrl').attr('uploadFileUrl'),
        accept: 'file',
        exts: 'zip|rar|7z|pdf|doc|docx|xls|xlsx|exe|mp3|mp4|m4v|jpg|png|jpeg|gif',
        size: 512000, // 50MB（需与后端配置保持一致）
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },

        before: function () {
            layer.load(2)
        },
        done: function (res) {
            layer.closeAll('loading');
            if (res.code === 200) {
                layer.msg(res.msg || '上传成功！！！', { icon: 1 }, 800);
                const name = this.item.data('field');
                $(`input[name=${name}]`).val(res.path);
            } else {
                layer.msg(res.msg || '上传失败', { icon: 2 }, 800);
            }
        },
        error: function (xhr, type, error) {
            layer.closeAll('loading');
            try {
                const res = JSON.parse(xhr.responseText);
                layer.msg(res.msg || `上传错误：${error}`, { icon: 2 });
            } catch (e) {
                layer.msg('网络异常，请稍后重试', { icon: 2 });
            }
        }
    });

    // 表单弹窗控制器
    function openForm(params = {}, url) {
        const options = {
            title: '操作信息',
            width: '1280px',
            height: '96%',
            ...params // 正确展开用户参数
        };

        // 如果有 isPopup 参数，将其添加到 URL 查询字符串中
        if (options.isPopup) {
            const separator = url.includes('?') ? '&' : '?';
            url = url + separator + 'isPopup=' + options.isPopup;
            delete options.isPopup;
        }

        layer.open({
            title: options.title, // 此时会正确显示 "添加位置"
            type: 2,
            shade: 0.8,
            content: url,
            area: [options.width, options.height]
        });
    }

    // 通用确认弹窗
    $('.c-confirm').click(function () {
        const url = $(this).attr('url');
        const title = $(this).attr('title');

        layer.confirm(title, {
            icon: $(this).attr('icon'),
            title: '特别提醒',
            skin: $(this).attr('skin'),
            maxWidth: 360
        }, function (index) {
            const loading = layer.load(2, { shade: [0.2, '#000'] });
            $.post(url).done(res => {
                res.code == 200
                    ? layer.msg(res.msg, { icon: 1, time: 2000 }, () => res.url ? location.href = res.url : location.reload())
                    : layer.msg(res.msg, { icon: 2, anim: 6 });
            }).always(() => layer.close(loading));
        });
    });
    // 直接执行操作弹窗
    $('.c-layer-msg').click(function () {
        const url = $(this).attr('data-url');

        const loading = layer.load(2, { shade: [0.2, '#000'] });
        $.post(url).done(res => {
            if (res.code == 200) {
                layer.msg(res.msg, { icon: 1, time: 2000 }, () => res.url ? location.href = res.url : location.reload())
            } else {
                layer.msg(res.msg, {
                    icon: 2,
                    anim: 6,
                    offset: 't'  // 从顶部弹出
                });
            }
        }).always(() => layer.close(loading));
    });


    // 图片预览功能
    $(document).on('click', '.c-imgshow', function () {
        const src = $(this).data('src');
        layer.open({
            type: 1,
            title: false,
            skin: 'c-preview-layer', // 自定义样式
            shadeClose: true,            // 开启遮罩关闭
            area: ['auto', 'auto'],      // 自适应大小
            content: `<div style="padding:15px;text-align:center;">
                     <img src="${src}" style="max-width:90vw;max-height:90vh;box-shadow:0 2px 12px 0 rgba(0,0,0,.1);">
                  </div>`
        });
    });
    // 微信联系方式弹窗
    $('.contact-us').on('click', () => {
        layer.open({
            title: '扫码添加技术微信',
            offset: '200px',
            closeBtn: 1,
            btn: 0,
            content: '<div><img src="/static/admin/images/wechat.jpg" alt="" width="300"></div>',
        });
    });
    // ---------------------------- 多语言切换功能 ----------------------------
    function initLangSwitch() {
        // 初始化语言切换按钮事件
        $('.lang-btn').on('click', function () {
            const lang = $(this).data('lang'); // 获取目标语言
            const url = $(this).data('url'); // 获取目标URL
            switchLanguage(lang, url); // 执行语言切换
        });
    }

    // 语言切换核心函数
    function switchLanguage(lang, url) {
        // 根据语言显示不同的加载提示
        const loadingText = lang === 'zh-cn' ? '切换语言中...' : 'Switching language...';
        const loadingIndex = layer.msg(loadingText, {
            icon: 16,
            time: 0
        });

        $.ajax({
            url: url, // 语言切换接口
            type: 'POST', // POST请求
            data: { lang: lang }, // 语言参数
            dataType: 'json', // 返回JSON格式
            success: function (response) {
                layer.close(loadingIndex); // 关闭加载提示

                if (response.code === 200) {
                    layer.msg(response.msg); // 显示后台返回的成功提示

                    // 延迟刷新让用户看到提示
                    setTimeout(function () {
                        window.location.reload(); // 刷新页面
                    }, 800);
                } else {
                    // 根据当前语言显示错误提示
                    const errorMsg = lang === 'zh-cn' ? '操作失败' : 'Operation failed';
                    layer.msg(response.msg || errorMsg);
                }
            },
            error: function () {
                layer.close(loadingIndex); // 关闭加载提示
                // 根据当前语言显示网络错误
                const errorMsg = lang === 'zh-cn' ? '网络错误，请重试' : 'Network error, please try again';
                layer.msg(errorMsg);
            }
        });
    }


    // 最新动态轮播效果
    function initNewsCarousel() {
        var $newsList = $('.c-news-list');
        var $newsItems = $('.c-news-item');
        var $dots = $('.c-news-dot');
        var currentIndex = 0;
        var totalItems = $newsItems.length;

        if (totalItems <= 1) return;

        // 初始化显示第一个
        $newsItems.hide().eq(0).show();

        // 自动轮播
        var interval = setInterval(function () {
            showNextNews();
        }, 4000);

        // 显示下一条新闻
        function showNextNews() {
            $newsItems.eq(currentIndex).fadeOut(500);
            currentIndex = (currentIndex + 1) % totalItems;
            $newsItems.eq(currentIndex).fadeIn(500);
            updateDots();
        }

        // 更新指示器
        function updateDots() {
            $dots.removeClass('active');
            $dots.eq(currentIndex).addClass('active');
        }

        // 点击指示器切换
        $dots.on('click', function () {
            var index = $(this).data('index');
            if (index !== currentIndex) {
                clearInterval(interval);
                $newsItems.eq(currentIndex).fadeOut(500);
                currentIndex = index;
                $newsItems.eq(currentIndex).fadeIn(500);
                updateDots();

                // 重启自动轮播
                interval = setInterval(function () {
                    showNextNews();
                }, 4000);
            }
        });

        // 鼠标悬停暂停轮播
        $('.c-news-card').hover(
            function () {
                clearInterval(interval);
            },
            function () {
                interval = setInterval(function () {
                    showNextNews();
                }, 4000);
            }
        );
    }

    // 初始化轮播
    initNewsCarousel();
    initLangSwitch(); // 初始化语言切换
});
