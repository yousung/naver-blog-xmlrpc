# PHP Naver Blog xmlrpc API #

네이버 블로그 xmlrpc API

MIT licensed.

#### Naver Xmlrpc API ####

- [관련 도움글](https://help.naver.com/support/contents/contents.nhn?serviceNo=520&categoryNo=1812)

## 설치 ##

PHP Composer 를 통해 패키지를 설치합니다.

`$ composer require lovizu/naver-blog-xmlrpc`

## 예제 ##

```
require 'vendor/autoload.php';

$blogId = '[string] 아이디';
$blogPass = '[string] API연결 암호';
$isSecret = '[bool] 게시물 공개 여부';
$naverBlog = new NaverBlogXml($blogId, $blogPass, $isSecret);

// 글쓰기
@ 제목 : [string]
@ 내용 : [string] 안내-img 태그로 작성된 이미지는 모두 네이버로 업로드
@ 카테고리 : [null|string] 주의-블로그 카테고리명과 띄어쓰기 까지 일치, 안내-미 입력시 기본 카테고리로 저장
@ 태그 : [null|array|string] 안내-배열 혹은 ',' 로 태그 구분
@ return : [integer] 포스트ID 안내-삭제, 수정할때 필요
$naverBlog->newBlog('제목', '내용', '카테고리', '태그');


// 글수정 (네이버 정책변경으로 글수정 불가, 기존글 삭제 후 새로 작성 로직)
@ 포스트ID : [integer]
@ 제목 : [string]
@ 내용 : [string] 안내-img 태그로 작성된 이미지는 모두 네이버로 업로드
@ 카테고리 : [null|string] 주의-블로그 카테고리명과 띄어쓰기 까지 일치, 안내-미 입력시 기본 카테고리로 저장
@ 태그 : [null|array|string] 안내-배열 혹은 ',' 로 태그 구분
@ return : [integer] 포스트ID 안내-삭제, 수정할때 필요
$naverBlog->editBlog($postId, '제목', '내용', '카테고리' ,'태그');

// 글삭제
@ 포스트ID : [integer]
@ return : [array]
$naverBlog->delBlog($postId);
```