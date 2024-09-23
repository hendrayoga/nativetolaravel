<?php

// Define your paths manually
$sourcePath = 'D:'; // Path to your public directory
$controllerPath = 'D:/path/Controllers'; // Path to your controllers folder
$viewPath = 'D:/path/resources/views'; // Path to your Blade views folder
$routeFilePath = 'D:/path/routes/web.php'; // Path to your routes/web.php

function moveFilesAndGenerate($dir, $baseDir = '')
{
    global $sourcePath;

 
    $files = scandir($dir);
    
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $currentPath = "$dir/$file";
        $relativePath = $baseDir ? "$baseDir/$file" : $file;

        if (is_dir($currentPath)) {
           
            moveFilesAndGenerate($currentPath, $relativePath);
        } else {
            if (pathinfo($file, PATHINFO_EXTENSION) === 'php') {
               
                if (strpos($relativePath, 'folder1/') === 0) {
                    $folderOrigin = 'folder1';
                } elseif (strpos($relativePath, 'folder/') === 0) {
                    $folderOrigin = 'folder';
                } else {
                    continue; 
                }
                
          
                processPhpFile($relativePath, $folderOrigin);
            }
        }
    }
}

function processPhpFile($relativeFilePath, $folderOrigin)
{
    global $sourcePath, $controllerPath, $viewPath, $routeFilePath;
    $subPath = substr($relativeFilePath, strlen($folderOrigin) + 1); 
    $dir = pathinfo($subPath, PATHINFO_DIRNAME); 
    $fileName = pathinfo($subPath, PATHINFO_FILENAME);

    $controllerDir = "$controllerPath/$folderOrigin" . ($dir !== '.' ? "/$dir" : '');
    $viewDir = "$viewPath/$folderOrigin" . ($dir !== '.' ? "/$dir" : '');

    // **Ensure directories are created**
    if (!is_dir($controllerDir)) {
        mkdir($controllerDir, 0755, true); 
    }
    if (!is_dir($viewDir)) {
        mkdir($viewDir, 0755, true); 
    }

    // Read the original PHP file
    $phpContent = file_get_contents("$sourcePath/$relativeFilePath");

    // Separate PHP logic and HTML content
    [$phpLogic, $htmlContent] = separatePhpAndHtml($phpContent);

    // Generate controller and Blade file
    $controllerFile = "$controllerDir/$fileName.php"; 
    $bladeFile = "$viewDir/$fileName.blade.php";
    file_put_contents($controllerFile, generateControllerCode($folderOrigin, $dir, $fileName, $phpLogic));
    file_put_contents($bladeFile, convertHtmlToBlade($htmlContent, $folderOrigin));
    file_put_contents($routeFilePath, generateRouteCode($folderOrigin, $dir, $fileName), FILE_APPEND);
}

function separatePhpAndHtml($content)
{
    $phpLogic = '';
    $htmlContent = '';

    preg_match_all('/<\?php(.*?)\?>/s', $content, $phpMatches);

    $phpLogic = implode("\n", $phpMatches[1]);

    $htmlContent = preg_replace('/<\?php(.*?)\?>/s', '', $content);

    return [$phpLogic, $htmlContent];
}

function convertHtmlToBlade($htmlContent, $folderOrigin)
{
    $bladeContent = $htmlContent;

    $bladeContent = preg_replace('/src="(\/?public\/' . $folderOrigin . '\/.*?)"/', 'src="{{ asset(\'$1\') }}"', $bladeContent);
    $bladeContent = preg_replace('/href="(\/?public\/' . $folderOrigin . '\/.*?)"/', 'href="{{ asset(\'$1\') }}"', $bladeContent);
    $bladeContent = preg_replace('/src="(\/?' . $folderOrigin . '\/.*?)"/', 'src="{{ asset(\'$1\') }}"', $bladeContent);
    $bladeContent = preg_replace('/href="(\/?' . $folderOrigin . '\/.*?)"/', 'href="{{ asset(\'$1\') }}"', $bladeContent);

    $bladeContent = preg_replace('/<\?php if \((.*?)\) : \?>/', '@if($1)', $bladeContent);
    $bladeContent = preg_replace('/<\?php elseif \((.*?)\) : \?>/', '@elseif($1)', $bladeContent);
    $bladeContent = preg_replace('/<\?php else : \?>/', '@else', $bladeContent);
    $bladeContent = preg_replace('/<\?php endif; \?>/', '@endif', $bladeContent);

    $bladeContent = preg_replace('/<\?php foreach \((.*?) as (.*?)\) : \?>/', '@foreach($1 as $2)', $bladeContent);
    $bladeContent = preg_replace('/<\?php endforeach; \?>/', '@endforeach', $bladeContent);

    return $bladeContent;
}

function generateControllerCode($folderOrigin, $dir, $fileName, $phpLogic)
{
    $namespace = 'App\Http\Controllers\\' . $folderOrigin;
    if ($dir !== '.' && $dir !== '') {
        $namespace .= '\\' . str_replace('/', '\\', $dir);
    }

    return "<?php

namespace $namespace;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class $fileName extends Controller
{
    public function index()
    {
        // Your extracted PHP logic
        $phpLogic

        // Pass variables to Blade template
        return view('$folderOrigin" . ($dir !== '.' ? ".$dir" : '') . ".$fileName', compact('your_variables_here'));
    }
}";
}

function generateRouteCode($folderOrigin, $dir, $fileName)
{
    
    $routePath = '/' . $folderOrigin;
    if ($dir !== '.' && $dir !== '') {
        $routePath .= '/' . $dir;
    }
    $routePath .= '/' . $fileName;
 
    $controllerNamespace = 'App\Http\Controllers\\' . $folderOrigin;
    if ($dir !== '.' && $dir !== '') {
        $controllerNamespace .= '\\' . str_replace('/', '\\', $dir);
    }
    $controllerNamespace .= '\\' . $fileName;

    $routeName = $folderOrigin;
    if ($dir !== '.' && $dir !== '') {
        $routeName .= '.' . str_replace('/', '.', $dir);
    }
    $routeName .= '.' . $fileName;

    return "Route::get('$routePath', [$controllerNamespace::class, 'index'])->name('$fileName');\n";
}

moveFilesAndGenerate($sourcePath);

echo "Files moved, logic separated, asset links updated, and routes generated.\n";
