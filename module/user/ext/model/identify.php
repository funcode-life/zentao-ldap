<?php
public function identify($account, $password) {
  if (0 == strcmp('$',substr($account, 0, 1))) {
    return parent::identify(ltrim($account, '$'), $password);
  } else {
    // 查询ldap服务器
    $ldap = $this->loadModel('ldap');
    $user_dn = $this->config->ldap->uid.'='.$account.','.$this->config->ldap->baseDN;
    $pass = $ldap->identify($this->config->ldap->host, $user_dn, $password);
    if (strcmp('Success', $pass) != 0) return false;

    // 检查本地账户是否存在，不存在直接新建
    $user = $this->dao->select('*')->from(TABLE_USER)
      ->where('account')->eq($account)
      ->andWhere('deleted')->eq(0)
      ->fetch();
    if (!$user) {
      $user = $this->dao->insert(TABLE_USER)
        ->set('account')->eq($account)
        ->set('realname')->eq($account)
        ->set('password')->eq(md5($password))
        ->exec();
    }

    $ip = $this->server->remote_addr;
    $last = $this->server->request_time;
    // 插入访问记录
    $this->dao->update(TABLE_USER)
      ->set('visits = visits + 1')
      ->set('ip')->eq($ip)
      ->set('last')->eq($last)
      ->where('account')->eq($account)
      ->exec();
    $user->last = date(DT_DATETIME1, $user->last);
    // 修改当前用户的密码为ldap密码
    $user->password = md5($password);
    return $user;
  }
}
