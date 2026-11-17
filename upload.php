<?php
// ফ্রিউইচের মূল অফিশিয়াল সাউন্ড ফোল্ডার
$fsSoundDir = "/usr/share/freeswitch/sounds/en/us/callie/";

if (!file_exists($fsSoundDir)) {
    @mkdir($fsSoundDir, 0755, true);
}

$response = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['voice_file']) && $_FILES['voice_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['voice_file'];
        
        // ফাইলের আসল নাম এবং এক্সটেনশন ঠিক রাখা
        $originalName = pathinfo($file['name'], PATHINFO_FILENAME);
        // বিশেষ caractère বা স্পেস রিমুভ করে ক্লিন নাম তৈরি করা
        $cleanName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $originalName);
        $fileName = $cleanName . "_" . time() . ".wav";
        
        $tempUploads = __DIR__ . "/uploads/";
        if (!file_exists($tempUploads)) {
            @mkdir($tempUploads, 0755, true);
        }

        $tempFilePath = $tempUploads . basename($file['name']);

        if (move_uploaded_file($file['tmp_name'], $tempFilePath)) {
            $finalDestination = $fsSoundDir . $fileName;

            // SoX দিয়ে একদম পারফেক্ট ফ্রিউইচ কম্প্যাটিবল WAV ফরম্যাটে কনভার্ট করা
            $soxPath = '/usr/bin/sox';
            if (!file_exists($soxPath)) {
                $soxPath = 'sox';
            }

            $cmd = "$soxPath " . escapeshellarg($tempFilePath) . " -t wavpcm -r 8000 -c 1 -b 16 " . escapeshellarg($finalDestination) . " 2>&1";
            
            $output = [];
            $returnCode = 0;
            exec($cmd, $output, $returnCode);

            // টেম্পোরারি ফাইল মুছে ফেলা
            @unlink($tempFilePath);

            if (file_exists($finalDestination) && filesize($finalDestination) > 0) {
                @chmod($finalDestination, 0644);
                @chown($finalDestination, 'freeswitch');

                // ব্রাউজারে শোনার জন্য লোকাল ফোল্ডারেও একটি কপি রাখা
                @copy($finalDestination, $tempUploads . $fileName);

                $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
                $fileUrl = $protocol . "://" . $_SERVER['HTTP_HOST'] . "/uploads/" . $fileName;

                $response = [
                    'status' => 'success',
                    'message' => 'ফাইল সফলভাবে কনভার্ট হয়ে ফ্রিউইচ ফোল্ডারে যুক্ত হয়েছে!',
                    'fs_path' => $fileName,
                    'file_url' => $fileUrl
                ];
            } else {
                $response = ['status' => 'error', 'message' => 'কনভার্শন এরর: ' . implode(" ", $output)];
            }
        } else {
            $response = ['status' => 'error', 'message' => 'ফাইল সার্ভারে আপলোড করা যায়নি।'];
        }
    } else {
        $response = ['status' => 'error', 'message' => 'দয়া করে একটি অডিও ফাইল সিলেক্ট করুন।'];
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PBX Voice Uploader for FreeSWITCH</title>
    <style>
        body { font-family: Arial, sans-serif; background: #0f172a; color: #f8fafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: #1e293b; padding: 30px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.4); width: 500px; text-align: center; }
        h2 { margin-bottom: 20px; color: #38bdf8; }
        input[type="file"] { width: 100%; padding: 12px; background: #334155; border: 1px dashed #64748b; border-radius: 6px; color: #fff; margin-bottom: 20px; cursor: pointer; box-sizing: border-box; }
        button { background: #2563eb; color: white; border: none; padding: 12px 20px; border-radius: 6px; font-size: 16px; cursor: pointer; width: 100%; font-weight: bold; transition: background 0.3s; }
        button:hover { background: #1d4ed8; }
        .alert { padding: 15px; border-radius: 6px; margin-bottom: 20px; font-size: 14px; text-align: left; word-break: break-all; }
        .alert.success { background: #065f46; color: #d1fae5; border: 1px solid #047857; }
        .alert.error { background: #991b1b; color: #fee2e2; border: 1px solid #b91c1c; }
        audio { width: 100%; margin-top: 15px; }
        .url-box { background: #0f172a; padding: 8px; border-radius: 4px; font-size: 12px; color: #38bdf8; margin-top: 6px; user-select: all; border: 1px solid #334155; text-align: left; }
        label { font-size: 11px; color: #94a3b8; display: block; margin-top: 8px; text-align: left; font-weight: bold; }
    </style>
</head>
<body>
    <div class="card">
        <h2>PBX Voice Uploader</h2>
        
        <?php if ($response): ?>
            <div class="alert <?php echo $response['status']; ?>">
                <strong><?php echo $response['message']; ?></strong>
                <?php if ($response['status'] === 'success'): ?>
                    <label>FreeSWITCH Database File Name (ডাটাবেজের greet_long এ শুধু এটি বসাবেন):</label>
                    <div class="url-box"><?php echo $response['fs_path']; ?></div>
                    
                    <label>Browser Preview URL:</label>
                    <div class="url-box"><?php echo $response['file_url']; ?></div>
                    
                    <audio controls>
                        <source src="<?php echo $response['file_url']; ?>" type="audio/wav">
                        আপনার ব্রাউজার অডিও ট্যাগ সমর্থন করছে না।
                    </audio>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <input type="file" name="voice_file" accept="audio/*" required>
            <button type="submit">Upload & Convert Voice</button>
        </form>
    </div>
</body>
</html>
