## Blesta Utility FTP

The _Blesta\Core\Util\FTP_ namespace can be used to make connections .

### Basic Usage

#### Making a connection

```
// Create an FTP connection
$options = array(
    'passive' => true,
    'port' => 21,
    'timeout' => 30,
    'curlOptions' => array(
        CURLOPT_PROTOCOLS => CURLPROTO_FTP,
    )
);
$ftp = new Ftp($server, $user, $password, $options);
$ftp->connect();

// Set a new server
$ftp->setServer($server);
$ftp->setCredentials($user, $pass);
$ftp->connect();
```

#### Read a file
This may throw an exception

```
$ftp->read('filepath');
```

#### Write a file
This may throw an exception

```
$ftp->write('remote_filepath', 'local_filepath');
```

#### List directory contents
This may throw an exception

```
$ftp->listDir('filepath');
```

#### Delete a file
This may throw an exception

```
$ftp->delete('filepath');
```
