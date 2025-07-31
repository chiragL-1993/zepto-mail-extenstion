<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?= $title ?></title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://live.zwidgets.com/js-sdk/1.2/ZohoEmbededAppSDK.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" />
    <script src="https://unpkg.com/tailwindcss-cdn@3.3.4/tailwindcss.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.css" />
    <script src="https://cdn.jsdelivr.net/gh/abpetkov/switchery/dist/switchery.min.js"></script>

    <!---  Custom Files --->
    <?php if (!empty($css)) {
        foreach ($css as $single_css) { ?>
            <link rel="stylesheet" href="<?= $single_css ?>" type="text/css">
            </link>
    <?php }
    } ?>
    <?php if (!empty($custom_js)) {
        foreach ($custom_js as $single_js) { ?>
            <script src="<?= $single_js ?>"></script>
    <?php }
    } ?>
</head>
<?= $body ?>
<script>
    var HEIGHT = <?= $height ?>;
    var WIDTH = <?= $width ?>;
    /*ZOHO.embeddedApp.init().then(function(){
        ZOHO.CRM.UI.Resize({
            height: <?= $height ?>,
            width: <?= $width ?>
          });
    });*/
</script>


</html>