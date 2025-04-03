<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Up to 3 Files</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        form {
            max-width: 600px;
            margin: auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        input[type="file"] {
            display: block;
            margin-bottom: 10px;
        }
        .preview {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .preview img, .preview video, .preview audio {
            max-width: 150px;
            max-height: 150px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .preview .file-item {
            position: relative;
        }
        .preview .file-item button {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
        }
        button {
            padding: 10px 20px;
            border: none;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
        .feedback {
            margin-top: 20px;
            font-size: 1.1em;
        }
        .feedback ul {
            list-style-type: none;
            padding: 0;
        }
        .feedback li {
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <h1>Upload Up to 3 Files</h1>
    <form id="uploadForm" action="https://media.sraws.com/upload.php" method="post" enctype="multipart/form-data">
        <input type="file" name="media[]" id="media" accept="image/*,video/*,audio/*" multiple>
        <div class="preview" id="preview"></div>
        <button type="submit">Upload</button>
    </form>
    <div id="feedback" class="feedback"></div>

    <script>
        const mediaInput = document.getElementById('media');
        const preview = document.getElementById('preview');
        const feedback = document.getElementById('feedback');
        let filesArray = [];

        function updatePreview() {
            preview.innerHTML = ''; // Clear previous previews

            filesArray.forEach((file, index) => {
                const reader = new FileReader();
                const fileItem = document.createElement('div');
                fileItem.classList.add('file-item');

                reader.onload = function(e) {
                    let mediaElement;
                    if (file.type.startsWith('image/')) {
                        mediaElement = document.createElement('img');
                        mediaElement.src = e.target.result;
                    } else if (file.type.startsWith('video/')) {
                        mediaElement = document.createElement('video');
                        mediaElement.src = e.target.result;
                        mediaElement.controls = true;
                    } else if (file.type.startsWith('audio/')) {
                        mediaElement = document.createElement('audio');
                        mediaElement.src = e.target.result;
                        mediaElement.controls = true;
                    }
                    fileItem.appendChild(mediaElement);
                    const removeButton = document.createElement('button');
                    removeButton.textContent = 'X';
                    removeButton.onclick = function() {
                        filesArray.splice(index, 1); // Remove file from the array
                        updatePreview(); // Update preview
                    };
                    fileItem.appendChild(removeButton);
                    preview.appendChild(fileItem);
                };

                reader.readAsDataURL(file);
            });
        }

        mediaInput.addEventListener('change', function(event) {
            const newFiles = Array.from(event.target.files);

            if (filesArray.length + newFiles.length > 3) {
                feedback.textContent = 'You can only select up to 3 files.';
                event.target.value = ''; // Clear the file input
                return;
            }

            filesArray = [...filesArray, ...newFiles];
            updatePreview();
            feedback.textContent = '';
        });

        document.getElementById('uploadForm').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting normally

            if (filesArray.length === 0) {
                feedback.textContent = 'No files selected.';
                return;
            }

            const formData = new FormData();
            filesArray.forEach(file => formData.append('media[]', file));

            fetch(this.action, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (Array.isArray(data)) {
                    feedback.innerHTML = ''; // Clear previous feedback
                    data.forEach(item => {
                        const listItem = document.createElement('li');
                        listItem.textContent = item.url ? `File uploaded successfully! File URL: ${item.url}` : `Error: ${item.error}`;
                        feedback.appendChild(listItem);
                    });
                } else {
                    feedback.textContent = `Unexpected response format.`;
                }
            })
            .catch(error => {
                feedback.textContent = `Error: ${error.message}`;
            });
        });
    </script>
</body>
</html>
