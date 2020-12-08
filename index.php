<?php

class GitHubEmails
{
    public function __construct()
    {
        $this->repository = "alissonlinneker/GitHub-Repository-Email-Scraper";
        $this->url = "https://api.github.com/";

        $this->user = "USER";
        $this->token = "TOKEN";
    }

    private function get($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_AUTOREFERER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_URL, $this->url.$url);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_USERPWD, $this->user.":".$this->token);
        curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/42.0.2311.90 Safari/537.36");

        $data = curl_exec($ch);
        curl_close($ch);

        return json_decode($data, 1);
    }

    public function get_stargazers_count()
    {
        $total = $this->get('repos/' . $this->repository);
        return $total["stargazers_count"];
    }

    public function get_stargazers()
    {
        $projeto = $this->repository;
        $results = array();
        $starts = floor($this->get_stargazers_count() / 100);

        for ($i = 1; $i <= ($starts == 0 ? 1 : $starts); $i++) {
            $data = $this->get('repos/' . $projeto . '/stargazers?page=' . $i . '&per_page=100');

            foreach ($data as $d) {
                $user = $this->get_userinfo($d["login"]);

                if ($user["email"] == "") {
                    $email = $this->search_email($d["login"]);
                    if ($email == "")
                        continue;
                }

                $results[$d["login"]]["nome"] = trim($user["name"]);
                $results[$d["login"]]["location"] = trim($user["location"]);
                $results[$d["login"]]["emaila"] = trim($user["email"]);
                $results[$d["login"]]["emailb"] = trim(@$email);
            }
        }

        return $results;
    }

    public function get_userinfo($user)
    {
        return $this->get('users/' . $user);
    }

    public function search_email($user)
    {
        $events = $this->get('users/' . $user . '/events/public');

        foreach ($events as $event) {
            if (!empty($event["payload"]["commits"])) {
                foreach ($event["payload"]["commits"] as $commits) {
                    if ($commits["author"]["name"] == $user and $commits["author"]["email"] != "")
                        return $commits["author"]["email"];
                }
            }
        }
    }
}

$lista = new GitHubEmails();

$lista = $lista->get_stargazers();

foreach($lista as $l => $lista)
    echo ($lista["emaila"] != "" ? $lista["emaila"] : $lista["emailb"]).", ".$lista["nome"].", ".$l.", ".$lista["location"]."<br>";
