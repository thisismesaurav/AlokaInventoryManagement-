<?php
$base_dir = __DIR__ . '/templates-html';
$dirs = ['ThemeHtml', 'backend', 'app'];

$header_content = '';
$footer_content = '';

foreach ($dirs as $dir) {
    $path = $base_dir . '/' . $dir;
    if (!is_dir($path)) continue;

    $files = scandir($path);
    foreach ($files as $file) {
        if (pathinfo($file, PATHINFO_EXTENSION) === 'html') {
            $filepath = $path . '/' . $file;
            $content = file_get_contents($filepath);

            // Use regex to find the start of content-page
            if (preg_match('/<div[^>]*class="content-page"[^>]*>/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                $content_start = $matches[0][1];
                
                // Find wrapper end
                if (preg_match('/<!--\s*Wrapper End\s*-->/i', $content, $end_matches, PREG_OFFSET_CAPTURE)) {
                    $content_end = $end_matches[0][1];
                    
                    $footer_start = strrpos(substr($content, 0, $content_end), '</div>');
                    
                    if (empty($header_content)) {
                        $header_content = substr($content, 0, $content_start);
                    }
                    if (empty($footer_content)) {
                        $footer_content = substr($content, $footer_start);
                    }

                    $new_content = substr($content, $content_start, $footer_start - $content_start);
                    
                    file_put_contents($filepath, $new_content);
                    echo "Processed: $file\n";
                } else {
                    echo "Skipped: $file (no wrapper end)\n";
                }
            } else {
                echo "Skipped: $file (no content-page)\n";
            }
        }
    }
}

file_put_contents(__DIR__ . '/raw_header.html', $header_content);
file_put_contents(__DIR__ . '/raw_footer.html', $footer_content);

echo "Done extracting raw_header.html and raw_footer.html!\n";
