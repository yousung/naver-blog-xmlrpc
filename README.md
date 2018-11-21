# PHP Naver Blog xmlrpc API #

네이버 블로그 xmlrpc API

#### Naver Xmlrpc API ####

- [관련 도움글](https://help.naver.com/support/contents/contents.nhn?serviceNo=520&categoryNo=1812)

## 설치 ##

PHP Composer 를 통해 패키지를 설치합니다.

`$ composer require lovizu/naver-blog-xmlrpc`

NAVER Blog 설정에서 API연결 암호를 얻습니다.

`https://admin.blog.naver.com/[네이버ID]/config/api`

![스크린샷](https://k.kakaocdn.net/dn/cu6laM/btqmshfUFqO/M9wwuaVVzEiusRvmVMyyck/img.jpg)



## 예제 ##

```
require 'vendor/autoload.php';

$blogId = '[string] 아이디';
$blogPass = '[string] API연결 암호';
$endPoint = '[string] 기본값 : https://api.blog.naver.com/xmlrpc';
$naverBlog = new NaverBlogXml($blogId, $blogPass, $endPoint);

// 기본사용
// Chain Method setItem 추가
// 제목과 내용을 작성하고 post()로 출력
// 내용에 이미지가 들어있을 경우 자동으로 네이버 서버에 업로드합니다.
// 작성 성공시 return 결과로 post id 출력 [수정, 삭제시 사용]
$naverBlog->setItem('제목', '내용')->post();

// 카테고리 추가시
// Chain Method setCategory 추가
// (string) 카테고리명 [띄어쓰기 주의]
$naverBlog->setItem('제목', '내용')->setCategory('카테고리명')->post();

// 태그 추가
// Chain Method setTags 추가
// (string|array) 배열 혹은 ','로 구분하여 작성
$naverBlog->setItem('제목', '내용')->setTags(['태그1', '태그2', '태그3'])->post();
or
$naverBlog->setItem('제목', '내용')->setTags('태그1,태그2,태그3')->post();

// 비공개글
// Chain Method setSecret 추가
$naverBlog->setItem('제목', '내용')->setSecret()->post();

// 수정
// (string|int) postId
* 네이버 정책변경으로 인하여 xmlrpc로 수정불가
* 현재 로직은 postId를 확인하여 기존 포스팅을 삭제하고 다시 작성하는 로직
$naverBlog->setItem('제목', '내용')->post('postId');

//삭제
// (string|int) postId
$naverBlog->delBlog('postId');
```

`TODO : phpunit`

MIT licensed.
