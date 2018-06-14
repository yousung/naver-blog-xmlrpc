<?php

namespace Lovizu\NaverXmlRpc;

use PhpXmlRpc\Value;
use PhpXmlRpc\Client;
use PhpXmlRpc\Request;

class NaverBlogXml
{
    private $blogId;
    private $blogPass;
    private $client;
    private $response;
    private $isView;
    private $title;
    private $context;
    private $category;
    private $tags;

    /**
     * NaverBlogXml constructor.
     *
     * @param        $blogId
     * @param        $blogPass
     * @param string $endPoint
     */
    public function __construct($blogId, $blogPass, $endPoint = 'https://api.blog.naver.com/xmlrpc')
    {
        $this->blogId = $blogId;
        $this->blogPass = $blogPass;
        $this->isView = true;
        $this->client = new Client($endPoint);
        $this->client->return_type = 'json';
        $this->client->setSSLVerifyPeer(false);
    }

    /**
     * @param string $title
     * @param string $context
     *
     * @return $this
     */
    public function setItem($title = '제목', $context = '내용')
    {
        $this->title = $title;
        $this->context = $context;

        return $this;
    }

    /**
     * @param null|array|string $tags [null] 태그
     *
     * @return $this
     */
    public function setTags($tags = [])
    {
        $this->tags = $tags;

        return $this;
    }

    /**
     * @param string $category
     *
     * @return $this
     *
     * @throws \Exception
     */
    public function setCategory($category)
    {
        if (!$category || !is_string($category)) {
            throw new \Exception('setCategory 항목 내용이 잘못되었습니다', 400);
        }

        $this->category = $category;

        return $this;
    }

    /**
     * 결과 추출.
     *
     * @param $method
     * @param $data
     *
     * @return array
     */
    private function result($method, $data)
    {
        $request = new Request($method, $data);
        $this->response = $this->client->send($request);

        return [
            'data' => $this->response->value(),
            'errcode' => $this->response->faultCode(),
            'errstr' => $this->response->faultString(),
        ];
    }

    /**
     * 이미지 추출 및 업로드.
     *
     * @param $context
     *
     * @return mixed
     */
    private function getImages()
    {
        $context = $this->context;
        preg_match_all("/<img[^>]*src=[\"']?([^>\"']+)[\"']?[^>]*>/i", $context, $item);
        $images = [];
        if ($item[1] && count($item[1])) {
            foreach ($item[1] as $image) {
                $images[] = $image;
            }
        }

        foreach ($images as $image) {
            $tempUrl = $this->uploadMedia($image);
            $replaceUrl = $tempUrl ?: $image;
            $context = str_replace($image, $replaceUrl, $context);
        }

        return $context;
    }

    /**
     * @param string $url [required] 이미지 URL
     *
     * @return null|string $url
     */
    private function uploadMedia($url)
    {
        try {
            $name = basename($url);
            $bits = file_get_contents($url);
            $mime = getimagesize($url)['mime'];

            $method = 'metaWeblog.newMediaObject';

            $struct = array(
                'bits' => new Value($bits, 'base64'),
                'type' => new Value($mime, 'string'),
                'name' => new Value($name, 'string'),
            );

            $media = array(
                new Value($this->blogId, 'string'),
                new Value($this->blogId, 'string'),
                new Value($this->blogPass, 'string'),
                new Value($struct, 'struct'),
            );

            $result = $this->result($method, $media);

            return $result['data']->me['struct']['url']->me['string'] ?: null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * @return array
     *
     * @throws \Exception
     */
    private function getStruct()
    {
        if (!$this->title || !$this->context) {
            throw  new \Exception('필수항목이 누락되었습니다.', 400);
        }

        $struct = [
            'title' => new Value($this->title, 'string'),
            'description' => new Value(nl2br($this->context), 'string'),
        ];

        if ($category = $this->category) {
            $struct['categories'] = new Value(strip_tags(trim($category)), 'string');
        }

        if ($tags = $this->tags) {
            $tags = is_array($tags) ? implode(',', $tags) : $tags;
            $struct['tags'] = new Value(strip_tags(trim($tags)), 'string');
        }

        return $struct;
    }

    /**
     * @param bool $isSecret
     *
     * @return $this
     */
    public function setSecret($isSecret = true)
    {
        $this->isView = $isSecret;

        return $this;
    }

    public function post($postId = null)
    {
        try {
            if ($postId) {
                return $this->editBlog($postId);
            } else {
                return $this->newBlog();
            }
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }

    public function newBlog()
    {
        $method = 'metaWeblog.newPost';

        try {
            $this->context = $this->getImages();
            $struct = $this->getStruct();

            $data = [
               new Value($this->blogId, 'string'),
               new Value($this->blogId, 'string'),
               new Value($this->blogPass, 'string'),
               new Value($struct, 'struct'),
               new Value($this->isView, 'boolean'),
           ];

            $result = $this->result($method, $data);
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }

        return $result['data']->me['string'];
    }

    private function delBlog($postId)
    {
        $method = 'blogger.deletePost';

        $data = [
            new Value('', 'string'),
            new Value($postId, 'string'),
            new Value($this->blogId, 'string'),
            new Value($this->blogPass, 'string'),
            new Value(true, 'boolean'),
        ];

        $rtn = $this->result($method, $data);
        $rtn['data'] = $rtn['data']->me['boolean'] ? 'success' : 'failed';

        return $rtn;
    }

    private function editBlog($postId)
    {
        $this->delBlog($postId);
        try {
            return $this->newBlog();
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
    }
}
