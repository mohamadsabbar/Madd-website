import { BrowserRouter, Navigate, Route, Routes } from 'react-router-dom'
import { AuthProvider } from './context/AuthContext'
import { SiteConfigProvider } from './context/SiteConfigContext'
import { MainLayout } from './layout/MainLayout'
import { HomePage } from './pages/HomePage'
import { LoginPage } from './pages/LoginPage'
import {
  AboutPage,
  CoveragePage,
  OffersPage,
  OrderPage,
  PlansPage,
  ProgrammingPage,
  SupportPage,
} from './pages/PublicPages'
import {
  BusinessHomePage,
  BusinessHrPage,
  BusinessJoinPage,
  BusinessPlansPage,
  BusinessPosPage,
  BusinessProgrammingPage,
  BusinessWebPage,
} from './pages/BusinessPages'
import {
  AdminBranding,
  AdminContent,
  AdminCoverage,
  AdminDashboard,
  AdminDigital,
  AdminFaq,
  AdminGuard,
  AdminLayout,
  AdminLeads,
  AdminLoginPage,
  AdminOffers,
  AdminOrder,
  AdminPagesEditor,
  AdminPlans,
  AdminProgramming,
  AdminSeo,
  AdminSettings,
  AdminSlider,
  AdminTrust,
  AdminVisibility,
} from './pages/AdminPages'
import { AdminMenu } from './pages/AdminMenu'

export default function App() {
  return (
    <SiteConfigProvider>
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="login" element={<LoginPage />} />

            <Route element={<MainLayout />}>
              <Route index element={<HomePage />} />
              <Route path="plans" element={<PlansPage />} />
              <Route path="offers" element={<OffersPage />} />
              <Route path="coverage" element={<CoveragePage />} />
              <Route path="support" element={<SupportPage />} />
              <Route path="order" element={<OrderPage />} />
              <Route path="programming" element={<ProgrammingPage />} />
              <Route path="about" element={<AboutPage />} />

              <Route path="business" element={<BusinessHomePage />} />
              <Route path="business/plans" element={<BusinessPlansPage />} />
              <Route path="business/pos" element={<BusinessPosPage />} />
              <Route path="business/hr" element={<BusinessHrPage />} />
              <Route path="business/programming" element={<BusinessProgrammingPage />} />
              <Route path="business/web" element={<BusinessWebPage />} />
              <Route path="business/join" element={<BusinessJoinPage />} />
            </Route>

            <Route path="admin">
              <Route index element={<AdminLoginPage />} />
              <Route element={<AdminGuard />}>
                <Route element={<AdminLayout />}>
                  <Route path="dashboard" element={<AdminDashboard />} />
                  <Route path="branding" element={<AdminBranding />} />
                  <Route path="content" element={<AdminContent />} />
                  <Route path="pages" element={<AdminPagesEditor />} />
                  <Route path="coverage" element={<AdminCoverage />} />
                  <Route path="trust" element={<AdminTrust />} />
                  <Route path="leads" element={<AdminLeads />} />
                  <Route path="slider" element={<AdminSlider />} />
                  <Route path="menu" element={<AdminMenu />} />
                  <Route path="order" element={<AdminOrder />} />
                  <Route path="offers" element={<AdminOffers />} />
                  <Route path="faq" element={<AdminFaq />} />
                  <Route path="plans" element={<AdminPlans />} />
                  <Route path="programming" element={<AdminProgramming />} />
                  <Route path="digital" element={<AdminDigital />} />
                  <Route path="visibility" element={<AdminVisibility />} />
                  <Route path="seo" element={<AdminSeo />} />
                  <Route path="settings" element={<AdminSettings />} />
                </Route>
              </Route>
            </Route>

            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </SiteConfigProvider>
  )
}
