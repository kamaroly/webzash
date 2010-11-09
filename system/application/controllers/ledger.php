<?php
class Ledger extends Controller {

	function Ledger()
	{
		parent::Controller();
		$this->load->model('Ledger_model');
		$this->load->model('Group_model');
	}

	function index()
	{
		redirect('ledger/add');
	}

	function add()
	{
		$this->load->library('validation');
		$this->template->set('page_title', 'New Ledger');

		/* Form fields */
		$data['ledger_name'] = array(
			'name' => 'ledger_name',
			'id' => 'ledger_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => $this->input->post('ledger_name'),
		);
		$data['ledger_group_id'] = $this->Group_model->get_all_groups();
		$data['op_balance'] = array(
			'name' => 'op_balance',
			'id' => 'op_balance',
			'maxlength' => '15',
			'size' => '15',
			'value' => $this->input->post('op_balance'),
		);

		/* Form validations */
		$this->form_validation->set_rules('ledger_name', 'Ledger name', 'trim|required|min_length[2]|max_length[100]|unique[ledgers.name]');
		$this->form_validation->set_rules('ledger_group_id', 'Parent group', 'trim|required|is_natural_no_zero');
		$this->form_validation->set_rules('op_balance', 'Opening balance', 'trim|currency');
		$this->form_validation->set_rules('op_balance_dc', 'Opening balance type', 'trim|required|is_dc');

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			$this->template->load('template', 'ledger/add', $data);
		}
		else
		{
			$data_name = $this->input->post('ledger_name', TRUE);
			$data_group_id = $this->input->post('ledger_group_id', TRUE);
			$data_op_balance = $this->input->post('op_balance', TRUE);
			$data_op_balance_dc = $this->input->post('op_balance_dc', TRUE);

			if ( ! $this->db->query("INSERT INTO ledgers (name, group_id, op_balance, op_balance_dc) VALUES (?, ?, ?, ?)", array($data_name, $data_group_id, $data_op_balance, $data_op_balance_dc)))
			{
				$this->messages->add('Error addding Ledger A/C', 'error');
				$this->template->load('template', 'group/add', $data);
			} else {
				$this->messages->add('Ledger A/C added successfully', 'success');
				redirect('account');
			}
		}
		return;
	}

	function edit($id)
	{
		$this->template->set('page_title', 'Edit Ledger');

		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1)
		{
			$this->messages->add('Invalid Ledger A/C', 'error');
			redirect('account');
			return;
		}

		/* Loading current group */
		$ledger_data_q = $this->db->query("SELECT * FROM ledgers WHERE id = ?", array($id));
		if ($ledger_data_q->num_rows() < 1)
		{
			$this->messages->add('Invalid Ledger A/C', 'error');
			redirect('account');
			return;
		}
		$ledger_data = $ledger_data_q->row();

		/* Form fields */
		$data['ledger_name'] = array(
			'name' => 'ledger_name',
			'id' => 'ledger_name',
			'maxlength' => '100',
			'size' => '40',
			'value' => $ledger_data->name,
		);
		$data['ledger_group'] = $this->Group_model->get_all_groups();
		$data['ledger_group_active'] = $ledger_data->group_id;
		$data['op_balance'] = array(
			'name' => 'op_balance',
			'id' => 'op_balance',
			'maxlength' => '15',
			'size' => '15',
			'value' => $ledger_data->op_balance,
		);
		$data['op_balance_dc'] = $ledger_data->op_balance_dc;
		$data['ledger_id'] = $id;

		/* Form validations */
		$this->form_validation->set_rules('ledger_name', 'Ledger name', 'trim|required|min_length[2]|max_length[100]|uniquewithid[ledgers.name.' . $id . ']');
		$this->form_validation->set_rules('ledger_group', 'Parent group', 'trim|required|is_natural_no_zero');
		$this->form_validation->set_rules('op_balance', 'Opening balance', 'trim|currency');
		$this->form_validation->set_rules('op_balance_dc', 'Opening balance type', 'trim|required|is_dc');

		if ($this->form_validation->run() == FALSE)
		{
			$this->messages->add(validation_errors(), 'error');
			/* Re-populating form */
			if ($this->input->post('submit', TRUE))
			{
				$data['ledger_name']['value'] = $this->input->post('ledger_name', TRUE);
				$data['ledger_group_active'] = $this->input->post('ledger_group', TRUE);
				$data['op_balance']['value'] = $this->input->post('op_balance', TRUE);
				$data['op_balance_dc'] = $this->input->post('op_balance_dc', TRUE);
			}
			$this->template->load('template', 'ledger/edit', $data);
		}
		else
		{
			$data_name = $this->input->post('ledger_name', TRUE);
			$data_group_id = $this->input->post('ledger_group', TRUE);
			$data_op_balance = $this->input->post('op_balance', TRUE);
			$data_op_balance_dc = $this->input->post('op_balance_dc', TRUE);
			$data_id = $id;

			if ( ! $this->db->query("UPDATE ledgers SET name = ?, group_id = ?, op_balance = ?, op_balance_dc = ? WHERE id = ?", array($data_name, $data_group_id, $data_op_balance, $data_op_balance_dc, $data_id)))
			{
				$this->messages->add('Error updating Ledger A/C', 'error');
				$this->template->load('template', 'ledger/edit', $data);
			} else {
				$this->messages->add('Ledger A/C updated successfully', 'success');
				redirect('account');
			}
		}
		return;
	}

	function delete($id)
	{
		/* Checking for valid data */
		$id = $this->input->xss_clean($id);
		$id = (int)$id;
		if ($id < 1)
		{
			$this->messages->add('Invalid Ledger A/C', 'error');
			redirect('account');
			return;
		}
		$data_present_q = $this->db->query("SELECT * FROM voucher_items WHERE ledger_id = ?", array($id));
		if ($data_present_q->num_rows() > 0)
		{
			$this->messages->add('Cannot delete non-empty Ledger A/C', 'error');
			redirect('account');
			return;
		}

		/* Deleting ledger */
		if ($this->db->query("DELETE FROM ledgers WHERE id = ?", array($id)))
		{
			$this->messages->add('Ledger A/C deleted successfully', 'success');
			redirect('account');
		} else {
			$this->messages->add('Error deleting Ledger A/C', 'error');
			redirect('account');
		}
		return;
	}
}