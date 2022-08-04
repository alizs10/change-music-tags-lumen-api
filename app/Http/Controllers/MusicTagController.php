<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class MusicTagController extends Controller
{

    public function update(Request $request)
    {
        $this->validate($request, [
            "music" => "required|file|mimes:mp3|max:30000",
            "title" => "required|string|max:90|min:1",
            "album" => "required|string|max:90|min:1",
            "artist" => "required|string|max:90|min:1",
            "genre" => "required|string|max:90|min:1",
            "year" => "required|integer|max:2050|min:1800",
            "track_number" => "required|integer|max:1000|min:0",
        ]);

        $data = $request->all();
        $filepath = storage_path('musics' . DIRECTORY_SEPARATOR . time() . '-' . substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, 5));
        $music_file = $request->file('music');
        $new_name = "{$data['artist']}-{$data['track_number']}{$data['title']}.{$music_file->extension()}";
        $res = $music_file->move($filepath, $new_name);
        $uploaded_path = $res->getPathname();
        // dd($uploaded_path);


        $TaggingFormat = 'UTF-8';

        $getID3 = new \getID3();
        $getID3->setOption(array('encoding' => $TaggingFormat));

        $tag_writer = new \getid3_writetags();
        $tag_writer->filename = $uploaded_path;
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
        $tag_writer->tag_data = $TagData;
        // write tags
        if ($tag_writer->WriteTags()) {
            
            return response()->download($filepath . DIRECTORY_SEPARATOR . $new_name);
            
        } else {
            return response()->json([
                "status" => false,
                "errors" => 'Failed to write tags!<br>' . implode('<br><br>', $tag_writer->errors)
            ]);
        }
    }
}
