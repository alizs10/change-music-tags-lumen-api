<?php

namespace App\Http\Controllers;

use App\Models\Download;
use App\Models\Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class MusicTagController extends Controller
{

    public function upload(Request $request)
    {
        $this->validate($request, [
            "music" => "required|file|mimes:mp3|max:30000"
        ]);
        $music_file = $request->file('music');

        $fileID = time() . '-' . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 5);
        $fileName = "{$fileID}.{$music_file->extension()}";
        $filePath = storage_path("uploads");
        $uploadPath = $music_file->move($filePath, $fileName);

        if ($uploadPath) {

            Upload::create([
                "path" => $uploadPath,
                "valid_until" => Carbon::now()->addMinutes(5)
            ]);

            return response()->json([
                "status" => "success",
                "fileID" => $fileID,
            ]);
        }

        return response()->json([
            "status" => "unsuccess"
        ]);
    }

    public function update(Request $request)
    {
        $this->validate($request, [
            "fileID" => "required|string",
            "title" => "required|string|max:90|min:1",
            "album" => "required|string|max:90|min:1",
            "artist" => "required|string|max:90|min:1",
            "genre" => "required|string|max:90|min:1",
            "year" => "required|integer|max:2050|min:1800",
            "track_number" => "required|integer|max:1000|min:0",
            "cover" => "nullable|image|mimes:jpg,jpeg",
        ]);

        $dir = storage_path('uploads');
        $files = glob($dir . "/$request->fileID.*");

        if (count($files) == 0) {
            return response()->json([
                "status" => false,
                "message" => "File not found"
            ]);
        }

        $data = $request->all();
        $filePath = $files[0];


        $TaggingFormat = 'UTF-8';
        $getID3 = new \getID3();
        $getID3->setOption(array('encoding' => $TaggingFormat));

        $tag_writer = new \getid3_writetags();
        $tag_writer->filename = $filePath;
        $tag_writer->tagformats = array('id3v1', 'id3v2.3');
        // set various options (optional)
        $tag_writer->overwrite_tags = true;
        $tag_writer->tag_encoding = $TaggingFormat;
        $tag_writer->remove_other_tags = true;
        // populate data array
        $TagData['title'][] = $data['title'];
        $TagData['artist'][] = $data['artist'];
        $TagData['album'][] = $data['album'];
        $TagData['year'][] = $data['year'];
        $TagData['track_number'][] = $data['track_number'];
        $TagData['genre'][] = $data['genre'];
        

        //set cover art
        if ($request->has('cover')) {

            $pictureFile = file_get_contents($request->file('cover')->getRealPath());
            $fd = fopen($request->file('cover')->getRealPath(), 'rb');
            $APICdata = fread($fd, filesize($request->file('cover')->getRealPath()));
            fclose($fd);

            if ($APICdata) {
                $TagData += array(
                    'attached_picture' => array(0 => array(
                        'data'          => $APICdata,
                        'picturetypeid' => '03',
                        'description'   => 'cover',
                        'mime'          => "image/jpeg"
                    ))
                );
            }
        }

        $tag_writer->tag_data = $TagData;
        // write tags
        if ($tag_writer->WriteTags()) {

            //move file and make it ready for download

            $oldPath = trim(str_replace(storage_path(), "", $filePath), "/");
            $newPath = "downloads" . DIRECTORY_SEPARATOR . $data['fileID'];
            $exploadedOldPath = explode(".", $filePath);
            $ext = $exploadedOldPath[count($exploadedOldPath) - 1];
            $newName = "{$data['artist']} - {$data['track_number']} {$data['title']}.{$ext}";
            $newPath = $newPath . DIRECTORY_SEPARATOR . $newName;

            $res = Storage::disk('local')->move($oldPath, $newPath);
            if ($res) {
                Download::create([
                    'path' => storage_path($newPath),
                    'valid_until' => Carbon::now()->addMinutes(5),
                    'fileID' => $data['fileID'],
                ]);
            }


            return response()->json([
                "status" => "success",
                "dlUrl" => route('download', ['id' => $data['fileID']]),
            ]);
        } else {
            return response()->json([
                "status" => false,
                "errors" => 'Failed to write tags!<br>' . implode('<br><br>', $tag_writer->errors)
            ]);
        }
    }


    public function download($id)
    {
        $dir = storage_path('downloads' . DIRECTORY_SEPARATOR . $id);
        $files = glob($dir . '/*.*');

        if (count($files) > 0 && file_exists($files[0])) {
            return response()->download($files[0]);
        }

        return response()->json([
            "status" => false,
            "errors" => 'File not found'
        ]);
    }
}
