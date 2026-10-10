import 'api_client.dart';

class CustomerRepository {
  CustomerRepository(this.api);
  final ApiClient api;

  Future<Map<String, dynamic>> dashboard() => api.get('/customer/dashboard');

  Future<Map<String, dynamic>> invoices() => api.get('/customer/invoices');

  Future<Map<String, dynamic>> usage() => api.get('/customer/usage');

  Future<Map<String, dynamic>> plans() => api.get('/customer/plans');

  Future<Map<String, dynamic>> renewalRequest({String? note, int? planId}) {
    return api.post('/customer/renewal-request', body: {
      if (note != null && note.isNotEmpty) 'note': note,
      if (planId != null) 'plan_id': planId,
    });
  }

  Future<Map<String, dynamic>> selfExtend() => api.post('/customer/self-extend');

  Future<Map<String, dynamic>> changePassword({
    required String current,
    required String password,
    required String confirmation,
  }) {
    return api.post('/customer/change-password', body: {
      'current_password': current,
      'password': password,
      'password_confirmation': confirmation,
    });
  }

  Future<Map<String, dynamic>> forgotPassword(String login) {
    return api.post('/customer/forgot-password', auth: false, body: {'login': login});
  }

  Future<Map<String, dynamic>> resetPassword({
    required String login,
    required String code,
    required String password,
    required String confirmation,
  }) {
    return api.post('/customer/reset-password', auth: false, body: {
      'login': login,
      'code': code,
      'password': password,
      'password_confirmation': confirmation,
    });
  }

  Future<Map<String, dynamic>> tickets() => api.get('/customer/tickets');

  Future<Map<String, dynamic>> createTicket({
    required String subject,
    required String body,
  }) {
    return api.post('/customer/tickets', body: {
      'subject': subject,
      'body': body,
    });
  }

  Future<Map<String, dynamic>> ticket(int id) => api.get('/customer/tickets/$id');

  Future<Map<String, dynamic>> replyTicket(int id, String body) {
    return api.post('/customer/tickets/$id/messages', body: {'body': body});
  }
}
