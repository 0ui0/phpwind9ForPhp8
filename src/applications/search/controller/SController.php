<?php

class SController extends PwBaseController {

	public function run() {
		$keywords = $this->getInput('keyword');
		// Force local search as cloud search is deprecated/down
		if(!Wekit::C('site','search.isopen')){
			$this->forwardRedirect(WindUrlHelper::createUrl('search/search/run', array('keyword' => $keywords)));
			$this->forwardAction('app/index/run?app=search',array('keywords' => $keywords));
		}

	}

}