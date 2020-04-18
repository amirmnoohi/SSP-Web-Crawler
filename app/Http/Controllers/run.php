<?php

namespace App\Http\Controllers;

use App\ip;
use App\Setting;
use Faker\Provider\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image as Image;


class run extends Controller
{
    public $x = "All/Conditions";
    public $y = "app/public/";
    public $path = "https://ssp-app.ca/img/stock/";

    public function get($id)
    {
        $list = array();
        $api = "https://ssp-app.ca/exercises/all?perPage=50000&category=" . $id;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_COOKIE, 'connect.sid=s%3A4xWJCjKJ2S4e4ANg7rxgt8ZLh4GyUR22.kGWVjAYYzCVD8Btll6D11ISWRVLR1j2E%2BpgB9FuatCI');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch), true);
        for ($i = 0; $i < count($result["data"]); $i++) {
            if (!isset($list[$result["data"][$i]["title"]]))
                $list[$result["data"][$i]["title"]] = 1;
            else
                $list[$result["data"][$i]["title"]]++;
        }
        $list_cop = $list;
        for ($i = 0; $i < count($result["data"]); $i++) {
			if ($result["data"][$i]["resources"] != false)
            for ($j = 0; $j < count($result["data"][$i]["resources"]); $j++) {
                $title = $result["data"][$i]["title"];
                $title = str_replace('/', "-", $title);
                $title = str_replace('|', "-", $title);
                $title = str_replace('>', "more than", $title);
                $title = str_replace('<', "less than", $title);
                $title = str_replace('"', "'", $title);
				try{
                if ($list[$result["data"][$i]["title"]] == 1)
                    Image::make($this->path . $result["data"][$i]["resources"][$j]["file"] . "/1000")->save(storage_path($this->y . $this->x . "/" . $title . "_" . ($j + 1) . ".jpg"));
                else {
                    Image::make($this->path . $result["data"][$i]["resources"][$j]["file"] . "/1000")->save(storage_path($this->y . $this->x . "/" . $title . ($list[$result["data"][$i]["title"]] - $list_cop[$result["data"][$i]["title"]] + 1) . "_" . ($j + 1) . ".jpg"));
                }
				}
				catch (\Exception $e){
					dump($this->y . $this->x . "/" . $title);
					continue;
				}
            }
            $list_cop[$result["data"][$i]["title"]]--;
        }
    }

    public function down($title)
    {
        $title = str_replace('/', "-", $title);
        $title = str_replace('|', "-", $title);
        $title = str_replace('>', "more than", $title);
        $title = str_replace('<', "less than", $title);
        if ($title[-1] == " ")
            $title = substr($title, 0, -1);
        $this->x = $this->x . "/" . $title;
        return $this->x;
    }

    public function up()
    {
        while ($this->x[-1] != "/") {
            $this->x = substr($this->x, 0, -1);
        }
        $this->x = substr($this->x, 0, -1);
        return $this->x;
    }

    public function buildTree($tree_array, $display_field, $children_field, $recursionDepth = 0)
    {

        for ($i = 0; $i < count($tree_array); $i++) {
            $this->down($tree_array[$i][$display_field]);
            try {
                Storage::disk('public')->makeDirectory($this->x);
            } catch (\Exception $e) {
                exit(0);
            }
            if (($tree_array[$i][$children_field]) != false)
                $this->buildTree($tree_array[$i][$children_field], $display_field, $children_field, $recursionDepth + 1);
            else {
                $this->get($tree_array[$i]["id"]);
                $this->up();
            }
        }
        $this->up();
    }

    public function index()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://ssp-app.ca/categories");
        curl_setopt($ch, CURLOPT_COOKIE, 'connect.sid=s%3A4xWJCjKJ2S4e4ANg7rxgt8ZLh4GyUR22.kGWVjAYYzCVD8Btll6D11ISWRVLR1j2E%2BpgB9FuatCI');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $result = json_decode(curl_exec($ch), true);
//        dd(count($result));
        $this->buildTree($result[15]["children"], 'title', 'children');
		dd("END : ".$result[15]["title"]);
//      foreach ($result as $row){
//            dump($row["title"]);
//        }
    }

}
