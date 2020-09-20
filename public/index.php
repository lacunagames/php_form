<?php
require_once('..\src_backend\multiform.class.php');

$formConfig = (object)[
  'formTitle' => 'Multi Contact Form',
  'legendTitle' => 'Contact',
  'legendName' => 'contact',
  'fieldTypes' => [
    (object)[
      'title' => 'Name',
      'rules' => ['required', 'name']
    ],
    (object)[
      'title' => 'Email',
      'rules' => ['required', 'email']
    ],
    (object)[
      'title' => 'Phone number',
      'rules' => ['required', 'phone']
    ],
  ]
];
$values = isset($_POST[$formConfig->legendName]) ? $_POST[$formConfig->legendName] : null;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$form = new MultiForm($formConfig, $values, $action);

if (isset($_GET['json'])) {
  echo $form->printJson();
} else {
?>
  <!doctype html>
  <html lang="en" dir="ltr">

  <head>
    <link rel="stylesheet" href="assets/bundle.css">
    </link>

    <script src=""></script>
    <meta charset="utf-8">
    <meta name="google" value="notranslate">
    <meta name="referrer" content="strict-origin">
  </head>

  <body>
    <?php echo $form->printForm(); ?>
    <script defer src="assets/bundle.js"></script>
  </body>

  </html>
<?php } ?>