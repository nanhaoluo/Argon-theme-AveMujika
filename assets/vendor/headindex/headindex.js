/*!
 * headIndex.js - 文章目录生成插件
 * 为 Argon 主题提供文章目录功能
 */
(function($) {
    'use strict';

    $.fn.headIndex = function(options) {
        var defaults = {
            articleWrapSelector: '#post_content',
            indexBoxSelector: '#leftbar_catalog',
            subItemBoxClass: 'index-subItem-box',
            scrollOffset: 80,
            activeClass: 'active'
        };

        var settings = $.extend({}, defaults, options);
        var $articleWrap = $(settings.articleWrapSelector);
        var $indexBox = $(settings.indexBoxSelector);

        if (!$articleWrap.length || !$indexBox.length) {
            return this;
        }

        // 查找所有标题元素
        var $headings = $articleWrap.find('h1, h2, h3, h4, h5, h6');
        
        if ($headings.length === 0) {
            $indexBox.html('<div class="no-headings">暂无目录</div>');
            return this;
        }

        // 生成目录HTML
        var indexHtml = '<ul class="catalog-list">';
        var currentLevel = 0;
        var indexCounter = 0;

        $headings.each(function(index) {
            var $heading = $(this);
            var level = parseInt(this.tagName.substring(1));
            var text = $heading.text().trim();
            var id = 'heading-' + index;
            
            // 为标题添加ID
            if (!$heading.attr('id')) {
                $heading.attr('id', id);
            } else {
                id = $heading.attr('id');
            }

            // 处理层级
            if (level > currentLevel) {
                for (var i = currentLevel; i < level; i++) {
                    if (i === currentLevel) {
                        indexHtml += '<li><ul class="' + settings.subItemBoxClass + '">';
                    } else {
                        indexHtml += '<li><ul>';
                    }
                }
            } else if (level < currentLevel) {
                for (var j = currentLevel; j > level; j--) {
                    indexHtml += '</ul></li>';
                }
            }

            indexHtml += '<li><a href="#' + id + '" class="catalog-link" data-target="' + id + '">' + text + '</a></li>';
            currentLevel = level;
            indexCounter++;
        });

        // 关闭所有未关闭的标签
        for (var k = 0; k < currentLevel; k++) {
            indexHtml += '</ul></li>';
        }
        indexHtml += '</ul>';

        $indexBox.html(indexHtml);

        // 绑定点击事件
        $indexBox.on('click', '.catalog-link', function(e) {
            e.preventDefault();
            var targetId = $(this).data('target');
            var $target = $('#' + targetId);
            
            if ($target.length) {
                $('html, body').animate({
                    scrollTop: $target.offset().top - settings.scrollOffset
                }, 500, 'easeOutExpo');
                
                // 更新活动状态
                $indexBox.find('.catalog-link').removeClass(settings.activeClass);
                $(this).addClass(settings.activeClass);
            }
        });

        // 滚动时高亮当前章节
        var throttleTimer = null;
        $(window).on('scroll', function() {
            if (throttleTimer) {
                clearTimeout(throttleTimer);
            }
            
            throttleTimer = setTimeout(function() {
                var scrollTop = $(window).scrollTop();
                var currentHeading = null;
                
                $headings.each(function() {
                    var $heading = $(this);
                    var headingTop = $heading.offset().top - settings.scrollOffset - 50;
                    
                    if (scrollTop >= headingTop) {
                        currentHeading = $heading.attr('id');
                    }
                });
                
                if (currentHeading) {
                    $indexBox.find('.catalog-link').removeClass(settings.activeClass);
                    $indexBox.find('.catalog-link[data-target="' + currentHeading + '"]').addClass(settings.activeClass);
                }
            }, 100);
        });

        return this;
    };

})(jQuery);