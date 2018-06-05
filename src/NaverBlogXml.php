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
    private $secret;

    /**
     * NaverBlogXml constructor.
     *
     * @param        $blogId   [required] 블로그ID
     * @param        $blogPass [required] API연결 암호
     * @param        $secret   [nullable] 블로그 공개여부
     * @param string $endPoint [nullable] API연결 URL
     */
    public function __construct($blogId, $blogPass, $secret = true, $endPoint = 'https://api.blog.naver.com/xmlrpc')
    {
        $this->blogId = $blogId;
        $this->blogPass = $blogPass;
        $this->secret = $secret;
        $this->client = new Client($endPoint);
        $this->client->return_type = 'json';
        $this->client->setSSLVerifyPeer(false);
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
    private function getImages($context)
    {
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
     * @param              $title    [required] 제목
     * @param              $context  [required] 내용
     * @param null|string  $category [null] 카테고리 (띄어쓰기까지 주의)
     * @param null|array|string $tags     [null] 태그
     *
     * @return array
     */
    private function getStruct($title, $context, $category = null, $tags = [])
    {
        $struct = [
            'title' => new Value($title, 'string'),
            'description' => new Value(nl2br($context), 'string'),
        ];

        if ($category) {
            $struct['categories'] = new Value(strip_tags(trim($category)), 'string');
        }

        if ($tags) {
            $tags = is_array($tags) ? implode(',', $tags) : $tags;
            $struct['tags'] = new Value(strip_tags(trim($tags)), 'string');
        }

        return $struct;
    }

    /**
     * @param                   $title    [require] 제목
     * @param                   $context  [require] 내용
     * @param null|string       $category [null] 카테고리
     * @param null|array|string $tags     [null] 태그
     *
     * @return array
     */
    public function newBlog($title, $context, $category = null, $tags = [])
    {
        $method = 'metaWeblog.newPost';

        $context = $this->getImages($context);
        $struct = $this->getStruct($title, $context, $category, $tags);

        $data = [
            new Value($this->blogId, 'string'),
            new Value($this->blogId, 'string'),
            new Value($this->blogPass, 'string'),
            new Value($struct, 'struct'),
            new Value($this->secret, 'boolean'),
        ];

        $result = $this->result($method, $data);

        return $result['data']->me['string'];
    }

    /**
     * @param int $postId [required] postId
     *
     * @return array
     */
    public function delBlog($postId)
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

    /**
     * @param int          $postId
     * @param              $title
     * @param              $context
     * @param null         $category
     * @param array|string $tags
     *
     * @return array
     */
    public function editBlog($postId, $title, $context, $category = null, $tags = [])
    {
        $this->delBlog($postId);

        return $this->newBlog($title, $context, $category, $tags);
    }
}
