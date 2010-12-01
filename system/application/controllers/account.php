<?php
class Account extends Controller {
	function index()
	{
		$this->load->model('Ledger_model');
		$this->template->set('page_title', 'Chart of accounts');
		$this->template->set('nav_links', array('group/add' => 'New Group', 'ledger/add' => 'New Ledger'));

		/* Calculating difference in Opening Balance */
		$total_op = $this->Ledger_model->get_diff_op_balance();
		if ($total_op > 0)
		{
			$this->messages->add("Difference in Opening Balance is Dr " . convert_cur($total_op), 'error');
		} else if ($total_op < 0) {
			$this->messages->add("Difference in Opening Balance is Cr " . convert_cur(-$total_op), 'error');
		}

		$this->template->load('template', 'account/index');
		return;
	}
}
