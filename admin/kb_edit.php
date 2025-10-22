<?php
/**
 * Edit Knowledge Base Article
 */

require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../classes/KnowledgeBase.php';
require_once __DIR__ . '/../classes/Category.php';

initSession();
requireRole(['admin', 'agent']);

$kb = new KnowledgeBase();
$categoryObj = new Category();

$error = '';
$success = '';
$kbId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($kbId <= 0) {
    redirectTo('kb_manage.php');
}

$article = $kb->getArticleById($kbId);
if (!$article) {
    redirectTo('kb_manage.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCSRFToken();
    
    $title = sanitizeInput($_POST['title'] ?? '');
    $content = sanitizeHTML($_POST['content'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $isPublished = isset($_POST['is_published']) ? 1 : 0;
    
    if (empty($title)) {
        $error = 'Title is required';
    } elseif (empty($content)) {
        $error = 'Content is required';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a category';
    } else {
        if ($kb->updateArticle($kbId, [
            'title' => $title,
            'content' => $content,
            'category_id' => $categoryId,
            'is_published' => $isPublished
        ])) {
            $success = 'Article updated successfully';
            $article = $kb->getArticleById($kbId); // Refresh data
        } else {
            $error = 'Failed to update article';
        }
    }
}

$categories = $categoryObj->getCategories(true);
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit KB Article - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/style.css">
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Edit Knowledge Base Article</h1>
                    <div class="btn-toolbar">
                        <a href="../kb_article.php?id=<?php echo $kbId; ?>" class="btn btn-outline-primary me-2" target="_blank">
                            View Article
                        </a>
                        <a href="kb_manage.php" class="btn btn-secondary">Back to List</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo escapeOutput($error); ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo escapeOutput($success); ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST">
                            <?php outputCSRFField(); ?>
                            
                            <div class="mb-3">
                                <label for="title" class="form-label">Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo escapeOutput($article['title']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category *</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">Select a category</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['category_id']; ?>"
                                                <?php echo ($article['category_id'] == $cat['category_id']) ? 'selected' : ''; ?>>
                                            <?php echo escapeOutput($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">Content *</label>
                                <textarea class="form-control" id="content" name="content" rows="15" required><?php echo htmlspecialchars($article['content'], ENT_QUOTES); ?></textarea>
                                <small class="text-muted">Use the rich text editor to format your content and add images</small>
                            </div>

                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="is_published" name="is_published"
                                       <?php echo $article['is_published'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_published">
                                    Published
                                </label>
                            </div>

                            <div class="mb-3">
                                <small class="text-muted">
                                    Created: <?php echo formatDate($article['created_at']); ?> | 
                                    Views: <?php echo $article['views']; ?>
                                </small>
                            </div>

                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">Update Article</button>
                                <a href="kb_manage.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- TinyMCE Rich Text Editor -->
    <!-- Replace YOUR_API_KEY_HERE with your actual TinyMCE API key from https://www.tiny.cloud/my-account/dashboard/ -->
    <script src="https://cdn.tiny.cloud/1/f5xc5i53b0di57yjmcf5954fyhbtmb9k28r3pu0nn19ol86c/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <script>
        tinymce.init({
            selector: '#content',
            height: 600,
            menubar: 'file edit view insert format tools table help',
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount', 'emoticons',
                'codesample', 'quickbars'
            ],
            toolbar: 'undo redo | blocks fontsize | ' +
                'bold italic underline strikethrough | forecolor backcolor | ' +
                'alignleft aligncenter alignright alignjustify | ' +
                'bullist numlist outdent indent | ' +
                'link image media table codesample emoticons | ' +
                'removeformat code fullscreen preview | help',
            toolbar_mode: 'sliding',
            quickbars_selection_toolbar: 'bold italic | quicklink h2 h3 blockquote',
            quickbars_insert_toolbar: 'quickimage quicktable',
            contextmenu: 'link image table',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; font-size: 16px; line-height: 1.6; max-width: 900px; margin: 0 auto; padding: 20px; }',
            
            // Image settings
            image_advtab: true,
            image_title: true,
            image_caption: true,
            image_description: true,
            automatic_uploads: true,
            file_picker_types: 'image',
            images_file_types: 'jpg,jpeg,jpe,jfi,jif,jfif,png,gif,bmp,webp',
            
            // Enable drag and drop - accept all image types
            paste_data_images: true,
            images_reuse_filename: true,
            file_picker_callback: function(callback, value, meta) {
                if (meta.filetype === 'image') {
                    var input = document.createElement('input');
                    input.setAttribute('type', 'file');
                    input.setAttribute('accept', 'image/*');
                    
                    input.onchange = function() {
                        var file = this.files[0];
                        var reader = new FileReader();
                        
                        reader.onload = function() {
                            var id = 'blobid' + (new Date()).getTime();
                            var blobCache = tinymce.activeEditor.editorUpload.blobCache;
                            var base64 = reader.result.split(',')[1];
                            var blobInfo = blobCache.create(id, file, base64);
                            blobCache.add(blobInfo);
                            callback(blobInfo.blobUri(), { title: file.name });
                        };
                        reader.readAsDataURL(file);
                    };
                    
                    input.click();
                }
            },
            
            // Image upload handler with better error handling
            images_upload_handler: function (blobInfo, progress) {
                return new Promise((resolve, reject) => {
                    const formData = new FormData();
                    formData.append('file', blobInfo.blob(), blobInfo.filename());
                    formData.append('action', 'upload_image');
                    
                    console.log('Uploading image:', blobInfo.filename());
                    
                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', 'kb_upload_image.php', true);
                    
                    xhr.upload.onprogress = function (e) {
                        if (e.lengthComputable) {
                            progress(e.loaded / e.total * 100);
                        }
                    };
                    
                    xhr.onload = function() {
                        console.log('Upload response:', xhr.status, xhr.responseText);
                        
                        if (xhr.status === 200) {
                            try {
                                const result = JSON.parse(xhr.responseText);
                                if (result.success) {
                                    console.log('Image uploaded successfully:', result.location);
                                    resolve(result.location);
                                } else {
                                    console.error('Upload failed:', result.error);
                                    reject(result.error || 'Image upload failed');
                                }
                            } catch (e) {
                                console.error('JSON parse error:', e);
                                reject('Invalid server response');
                            }
                        } else {
                            reject('HTTP Error: ' + xhr.status);
                        }
                    };
                    
                    xhr.onerror = function () {
                        console.error('Network error during upload');
                        reject('Image upload failed: Network error');
                    };
                    
                    xhr.send(formData);
                });
            },
            
            // Setup callback for debugging
            setup: function(editor) {
                editor.on('init', function() {
                    console.log('TinyMCE initialized successfully');
                });
                
                editor.on('drop', function(e) {
                    console.log('Drop event detected');
                });
                
                editor.on('paste', function(e) {
                    console.log('Paste event detected');
                });
            },
            
            // Additional settings for better UX
            branding: false,
            promotion: false,
            resize: true,
            elementpath: false,
            statusbar: true,
            
            // Font sizes
            fontsize_formats: '8pt 10pt 12pt 14pt 16pt 18pt 24pt 36pt 48pt',
            
            // Block formats
            block_formats: 'Paragraph=p; Heading 1=h1; Heading 2=h2; Heading 3=h3; Heading 4=h4; Preformatted=pre; Blockquote=blockquote',
            
            // Code sample languages
            codesample_languages: [
                {text: 'HTML/XML', value: 'markup'},
                {text: 'JavaScript', value: 'javascript'},
                {text: 'CSS', value: 'css'},
                {text: 'PHP', value: 'php'},
                {text: 'Python', value: 'python'},
                {text: 'Java', value: 'java'},
                {text: 'C#', value: 'csharp'},
                {text: 'SQL', value: 'sql'},
                {text: 'Bash', value: 'bash'}
            ]
        });
    </script>
</body>
</html>
