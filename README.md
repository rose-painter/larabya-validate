<h1 align="center"> DTO参数验证器 </h1>
<p align="center"> DTO参数验证器</p>

## 安装

```shell
$ composer require dtovalidate -vvv
```

## 用例
你可以在 Laravel的表单验证中看到完整的使用示例：https://learnku.com/docs/laravel/8.5/validation/10378

```php

$validator = ValidatorFacade::make(array(
    'id'=>12,
    'en_name'=>'堆放室'), [
    'id' => 'required',
    'en_name' => 'required|alpha:ascii'
],[
    'id.required' => 'We need to know your id!',
    'en_name.required' => 'We need to know your name!',
    'en_name.alpha' => 'your name must be alpha:ascii!',
]);

if($validator->fails()){
    print_r($validator->errors());
}

```

## 更新日志
 - 2024-02-29 创建
## 提示

- Unique等数据库查询验证，文件验证均被移除。

- 如果你有更多好用的验证请添加在 `src/Concerns/ValidatesAttributes`中，以 `validate`开头，比如`validateJson`。
- 然后你可以在Rule 中添加对应的@method static json()

## Contributing

You can contribute in one of three ways:

1. File bug reports using the [issue tracker](https://github.com/duomai/validation/issues).
2. Answer questions or fix bugs on the [issue tracker](https://github.com/duomai/validation/issues).
3. Contribute new features or update the wiki.

_The code contribution process is not very formal. You just need to make sure that you follow the PSR-0, PSR-1, and PSR-2 coding guidelines. Any new code contributions must be accompanied by unit tests where applicable._

## License

MIT