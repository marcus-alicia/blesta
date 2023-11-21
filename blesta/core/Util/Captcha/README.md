## Blesta Captchas

The _Blesta\Core\Util\Captcha_ namespace can be used to create instances of
particular captcha systems useful for preventing spam or abuse.

### Basic Usage

#### Factories

##### Captcha Factory

The _Blesta\Core\Util\Captcha\CaptchaFactory_ provides methods to instantiate
specific captcha instances.

```
use Blesta\Core\Util\Captcha\CaptchaFactory;

$factory = new CaptchaFactory();

// Create an instance of Google reCaptcha
$reCaptchaOptions = [
    'site_key' => 'abc123',
    'shared_key' => 'qrstuv',
    'lang' => 'en',
    'ip_address' => '127.0.0.1'
];
// Blesta\Core\Util\Captcha\Common\CaptchaInterface
$reCaptcha = $factory->reCaptcha($reCaptchaOptions);
```

#### CaptchaInterface

A captcha instance supports a few basic methods.

The following examples use reCaptcha.

##### ::setOptions

Sets all of the options necessary for the captcha to operate.

```
$reCaptchaOptions = [
    'site_key' => 'abc123',
    'shared_key' => 'qrstuv',
    'lang' => 'en',
    'ip_address' => '127.0.0.1'
];

$reCaptcha->setOptions($reCaptchaOptions);
```

##### ::buildHtml

Generates the HTML necessary to render the captcha.

```
$html = $reCaptcha->buildHtml();
```

##### ::verify

Verifies whether or not the captcha was successfully passed by the user.

```
$response = isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
$success = $reCaptcha->verify(['response' => $response]);
```

##### ::errors

Retrieves a set of errors that may have been set by the captcha for any of the
method calls above.

```
$errors = $reCaptcha->errors();
```

#### Options

##### Google reCaptcha

Google reCaptcha supports the following captcha options:

* site_key - The reCaptcha site key
* shared_key - The reCaptcha shared key
* lang - This is the two-character language code representing the language
to use for the reCaptcha (e.g. 'en')
* ip_address - (optional) - The IP address of the user to verify the captcha
