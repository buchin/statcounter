<?php namespace Buchin\Statcounter;

class Statcounter extends Api
{
    // $mode can be 'top' or 'recent'
    public function getKeywords($project_ids = [], $max = 1000, $mode = "recent"): array
    {
        $projects = $this->get_user_project_details();
        $keywords = [];

        if(empty($project_ids)) {

            foreach ($projects as $project) {
                $project_id = (int) $project["project_id"];
                $project_ids[] = $project_id;
            }
        }

        if ($mode === "top") {
            $keywords = $this->get_keyword_analysis($project_ids, $max);
        } elseif ($mode === "recent") {
            $keywords = $this->get_recent_keywords($project_ids, $max);
        }

        $keywords = array_map(function ($keyword) {
            $keyword = explode(" ", $keyword);

            if(preg_match('/^\d+$/', $keyword[0])){
                unset($keyword[0]);
            }

            $keyword = str_replace(date('Y'), (int)date('Y')+1, $keyword);
            $keyword = str_replace(range('1900', date('Y')-1), date('Y'), $keyword);

            return implode(" ", $keyword);
        }, $keywords);

        $keywords = array_filter($keywords, function ($keyword){
            $word_count = explode(" ", $keyword);

            return $word_count > 2;
        });

        $keywords = array_filter($keywords, function ($keyword){
            $banned_words = ['star sessions', 'candydoll', 'brima', 'vipergirls', 'imxto', 'film semi', 'bella k', 'ams modeling', 'vladmodels'];

            foreach ($banned_words as $banned_word) {
                if(str_contains($keyword, $banned_word)){
                    return false;
                }
            }

            return true;
        });

        $keywords = array_filter($keywords);
        $keywords = array_unique($keywords);

        shuffle($keywords);

//        $keywords = array_slice($keywords, 0, $max);

        return $keywords;
    }

    public function get_recent_keywords($project_ids, $max = 1000)
    {
        $keywords = [];
        $max *= 5;

        foreach ($project_ids as $project_id) {
            $keywords = array_merge(
                $keywords,
                $this->get_recent_keyword_activity($project_id, $max, false)
            );
        }

        return array_values($keywords);
    }

    /**
     * @throws \Exception
     */
    public function get_recent_keyword_activity(
        $project_id,
        $num_of_results,
        $exclude_encrypted_kws = false
    ) {
        $this->sc_query_string =
            "&s=keyword-activity" .
            "&pi=" .
            $project_id .
            "&n=" .
            $num_of_results .
            "&eek=" .
            $exclude_encrypted_kws;

        $url = $this->build_url_json("stats");

        $json = @file_get_contents($url);

        if ($json) {
            $json = json_decode($json);

            $result = [];

            if (!isset($json->sc_data)) {
                return $result;
            }

            foreach ($json->sc_data as $record) {
                $keyword = $record->keyword !== '***Encrypted Search***' ?: $this->extractKeyword($record);

                $result[] = $keyword;
            }

            return $result;
        }

        throw new \Exception(
            "Error: Check your project ID and login credentials."
        );
    }

    public function get_keyword_analysis($project_ids, $max = 1000)
    {
        $max *= 5;
        $this->sc_query_string = "&s=keyword_analysis" . "&eek=0&n=" . $max;

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
                $keyword = $record->keyword !== '***Encrypted Search***' ?: $this->extractKeyword($record);
                $keywords[] = $keyword;
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

    private function extractKeyword($record): string
    {
        $arr = explode('/', $record->page_url);
        $lengths = array_map('strlen', $arr);
        $max_lengths = max($lengths);
        $index = array_search($max_lengths, $lengths);

        $slug = $arr[$index];

        $path = str_replace('-', ' ', $slug);

        if(str_contains($path, '.pages.dev')){
            return '';
        }

        if(str_contains($path, '.')){
            $path = explode('.', $path);

            $path = $path[0];
        }

        return str_replace('-', ' ', $path);
    }
}
