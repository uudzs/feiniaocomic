<?php

declare(strict_types=1);

namespace app\index\controller;

use app\BaseController;
use think\facade\View;
use think\facade\Cache;
use app\common\model\manage\Page as PageModel;

class Index extends BaseController
{
    /**
     * 获取带随机偏移的缓存数据
     */
    private function getCacheWithRandom(string $key, callable $fetcher, int $cacheTime = 300, int $maxItems = 50): array
    {
        $data = Cache::get($key);
        if (empty($data)) {
            $data = $fetcher();
            Cache::set($key, $data, $cacheTime);
        }

        // 如果数据量足够，进行随机取样
        if (count($data) > $maxItems) {
            shuffle($data);
            $data = array_slice($data, 0, $maxItems);
        }

        return $data;
    }

    public function index()
    {
        $data = [
            'comic' => [],
            'novel' => [],
        ];

        // 如果安装了漫画模块
        if (module_installed('comic')) {
            $data['comic'] = $this->getComicData();
        }

        // 如果安装了小说模块（预留）
        if (module_installed('novel')) {
            $data['novel'] = $this->getNovelData();
        }

        View::assign($data);
        return View::fetch();
    }

    /**
     * 获取漫画模块数据
     */
    private function getComicData(): array
    {
        try {
            $Comic = \module\comic\model\Comic::class;
            $Tag = \module\comic\model\Tag::class;
            $SearchRecord = \module\comic\model\SearchRecord::class;
            $ComicCate = \module\comic\model\ComicCate::class;
            $ComicChapter = \module\comic\model\ComicChapter::class;

            // 1. Banner轮播（热门且有banner图的）
            $banners = $this->getCacheWithRandom('index_comic_banners', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('banner', '<>', '')
                    ->where('cover', '<>', '')
                    ->orderRaw('RAND()')
                    ->limit(5)
                    ->select()
                    ->toArray();
            }, 3600);

            // 2. 热门推荐（随机打乱，每天变化）
            $hotComics = $this->getCacheWithRandom('index_comic_hot', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('is_hot', 1)
                    ->order('view_count', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 3. 编辑推荐
            $recommendComics = $this->getCacheWithRandom('index_comic_recommend', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('is_recommend', 1)
                    ->orderRaw('RAND()')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 4. 最新更新
            $latestComics = $this->getCacheWithRandom('index_comic_latest', function () use ($Comic, $ComicChapter) {
                return $Comic::where('status', 1)
                    ->order('updated_at', 'desc')
                    ->limit(18)
                    ->select()
                    ->each(function ($item, $key) use ($ComicChapter) {
                        $item->last_chapter = $ComicChapter::where('status', 1)->where('comic_id', $item->id)->order('sort')->value('title');
                        return $item;
                    })
                    ->toArray();
            }, 3600);

            // 5. 高分佳作
            $topRatedComics = $this->getCacheWithRandom('index_comic_top_rated', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('score', '>', 0)
                    ->order('score', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 6. 人气收藏榜（收藏数最多的）
            $favRankComics = $this->getCacheWithRandom('index_comic_fav_rank', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('fav_count', '>', 0)
                    ->order('fav_count', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 7. 飙升榜（浏览量增长最快 - 用随机模拟）
            $soaringComics = $this->getCacheWithRandom('index_comic_soaring', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('view_count', '>', 100)
                    ->orderRaw('RAND() * view_count DESC')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 8. 连载中作品
            $ongoingComics = $this->getCacheWithRandom('index_comic_ongoing', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->where('update_status', 0)
                    ->order('updated_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->toArray();
            }, 3600);

            // 9. 已完结作品
            $completedComics = $this->getCacheWithRandom('index_comic_completed', function () use ($Comic, $ComicChapter) {
                return $Comic::where('status', 1)
                    ->where('update_status', 1)
                    ->order('updated_at', 'desc')
                    ->limit(10)
                    ->select()
                    ->each(function ($item, $key) use ($ComicChapter) {
                        $item->chapter_count = $ComicChapter::where('status', 1)->where('comic_id', $item->id)->count();
                        return $item;
                    })
                    ->toArray();
            }, 3600);

            // 10. 随机精选（发现页用）
            $randomComics = $this->getCacheWithRandom('index_comic_random', function () use ($Comic) {
                return $Comic::where('status', 1)
                    ->orderRaw('RAND()')
                    ->limit(12)
                    ->select()
                    ->toArray();
            }, 3600);

            // 11. 热门标签
            $hotTags = $this->getCacheWithRandom('index_comic_hot_tags', function () use ($Comic, $Tag) {
                $tags = $Tag::where('status', 1)
                    ->order('click', 'desc')
                    ->limit(30)
                    ->select()
                    ->toArray();

                foreach ($tags as &$tag) {
                    $tag['comic_count'] = $Comic::where('status', 1)
                        ->whereLike('tags', '%' . $tag['title'] . '%')
                        ->count();
                }

                return $tags;
            }, 3600);

            // 12. 热门搜索词
            $hotKeywords = $this->getCacheWithRandom('index_comic_hot_keywords', function () use ($SearchRecord) {
                return $SearchRecord::where('search_count', '>', 0)
                    ->order('search_count', 'desc')
                    ->limit(20)
                    ->select()
                    ->toArray();
            }, 3600);

            // 13. 分类
            $cates = $this->getCacheWithRandom('index_comic_cates', function () use ($ComicCate, $Comic) {
                return $ComicCate::where('status', 1)
                    ->order('sort')
                    ->select()
                    ->each(function ($item, $key) use ($Comic) {
                        $item->comic_count = $Comic::where('status', 1)->where('cateid', $item->id)->count();
                        return $item;
                    })
                    ->toArray();
            }, 3600);

            return [
                'installed' => true,
                'banners' => $banners,
                'hotComics' => $hotComics,
                'recommendComics' => $recommendComics,
                'latestComics' => $latestComics,
                'topRatedComics' => $topRatedComics,
                'favRankComics' => $favRankComics,
                'soaringComics' => $soaringComics,
                'ongoingComics' => $ongoingComics,
                'completedComics' => $completedComics,
                'randomComics' => $randomComics,
                'hotTags' => $hotTags,
                'hotKeywords' => $hotKeywords,
                'cates' => $cates,
            ];
        } catch (\Exception $e) {
            return ['installed' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 获取小说模块数据（预留接口）
     */
    private function getNovelData(): array
    {
        // TODO: 实现小说模块数据获取
        return ['installed' => false];
    }

    public function app()
    {
        return View::fetch();
    }

    public function single()
    {
        $id = input('id/s', '');
        if (empty($id)) {
            return $this->error('单页ID不能为空');
        }
        $data = (new PageModel)->getByIdentifier($id);

        $seo = get_seo('comic', 'single_page', [
            'single_page_title' => $data['title'],
            'single_page_content' => mb_substr(strip_tags($data['content']), 0, 100),
            'single_page_publish_time' => $data['created_at'],
        ]);

        return View::fetch('single', [
            'id' => $id,
            'metatitle' => $seo ? $seo['title'] : '单页详情',
            'metakeywords' => $seo ? $seo['keywords'] : '',
            'metadescription' => $seo ? $seo['description'] : '',
            'data' => $data,
        ]);
    }

    public function contactus()
    {
        return View::fetch();
    }
}
