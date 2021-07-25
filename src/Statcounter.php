<?php namespace Buchin\Statcounter;

class Statcounter extends Api
{
    // $mode can be 'top' or 'recent'
    public function getKeywords($max = 1000, $mode = "top")
    {
        $projects = $this->get_user_project_details();
        $keywords = [];

        $project_ids = [];
        foreach ($projects as $project) {
            $project_id = (int) $project["project_id"];
            $project_ids[] = $project_id;
        }

        if ($mode === "top") {
            $keywords = $this->get_keyword_analysis($project_ids, $max);
        } elseif ($mode === "recent") {
            $keywords = $this->get_recent_keywords($project_ids, $max);
        }

        $keywords = array_unique($keywords);
        shuffle($keywords);

        $keywords = array_slice($keywords, 0, $max);

        return $keywords;
    }

    public function get_recent_keywords($project_ids, $max = 1000)
    {
        $keywords = [];
        $max *= 5;

        foreach ($project_ids as $project_id) {
            $keywords = array_merge(
                $keywords,
                $this->get_recent_keyword_activity($project_id, $max, true)
            );
        }

        return array_values($keywords);
    }

    public function get_keyword_analysis($project_ids, $max = 1000)
    {
        $max *= 5;
        $this->sc_query_string = "&s=keyword_analysis" . "&eek=1&n=" . $max;

        foreach ($project_ids as $project_id) {
            $this->sc_query_string .= "&pi=" . $project_id;
        }

        $url = $this->build_url_json("stats");

        $json = @file_get_contents($url);
        $json = json_decode($json);

        if (!isset($json->project)) {
            return [];
        }

        $keywords = [];

        foreach ($json->project as $project) {
            if (!isset($project->sc_data)) {
                continue;
            }

            foreach ($project->sc_data as $record) {
                $keywords[] = $record->keyword;
            }
        }

        return $keywords;
    }

    public function build_url_json($function)
    {
        $base = $this->sc_base_url . "/" . $function . "/";

        $this->sc_query_string =
            "?vn=" .
            $this->sc_version_num .
            "&t=" .
            time() .
            "&u=" .
            $this->sc_username .
            $this->sc_query_string .
            "&f=json";

        $sha1 = sha1($this->sc_query_string . $this->sc_password);

        $url = $base . $this->sc_query_string . "&sha1=" . $sha1;

        return $url;
    }
}
