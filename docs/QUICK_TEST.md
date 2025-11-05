# ⚡ Quick Test Guide

## 🚀 After Deployment Completes

### Step 1: Verify Backend (30 seconds)

```bash
cd "/Users/benjaminoats/Library/CloudStorage/SynologyDrive-File-sync/Coding/SEO AI Meta Generator/seo-ai-meta-generator"
./verify-deployment.sh
```

**Or manually:**
```bash
curl "https://alttext-ai-backend.onrender.com/billing/plans?service=seo-ai-meta"
```

**Expected:** JSON with plans showing Free: 10, Pro: 100, Agency: 1000

### Step 2: Test WordPress Plugin (2 minutes)

1. **Open WordPress Admin**
   - Go to: `http://localhost:8082/wp-admin`
   - Navigate to: **Posts** → **SEO AI Meta**

2. **Login/Register**
   - Click **"Login"** button in header
   - Click **"Register"** tab
   - Enter email: `test@example.com`
   - Enter password: `testpass123`
   - Click **"Register"**

3. **Verify Dashboard**
   - ✅ Should show: "0 of 10 generations used"
   - ✅ Progress bar at 0%
   - ✅ Plans show SEO AI Meta pricing
   - ✅ "Generate All" button visible

4. **Test Meta Generation**
   - Go to any post editor
   - Scroll to "SEO AI Meta" meta box
   - Click "Generate Meta Tags"
   - Verify meta title and description appear

### Step 3: Test Bulk Generate (2 minutes)

1. **Go to Bulk Generate Tab**
   - Click **"Bulk Generate Meta"** tab
   - See list of posts without meta

2. **Generate Meta**
   - Select some posts
   - Click **"Generate Selected"**
   - Watch progress bar
   - Verify success messages

3. **Check Usage**
   - Go back to **Dashboard** tab
   - Verify usage increased
   - Progress bar should update

## ✅ Success Indicators

### Backend Working:
- ✅ Health endpoint returns 200
- ✅ Plans endpoint returns SEO AI Meta plans
- ✅ Usage endpoint requires auth (401 without token)

### Plugin Working:
- ✅ Login/Register works
- ✅ Dashboard shows correct usage (0/10)
- ✅ Plans display correctly
- ✅ Meta generation works
- ✅ Usage tracking works

## 🆘 Common Issues

### "Backend not responding"
- Check Render dashboard - is it deployed?
- Wait 2-5 minutes for deployment
- Check backend URL is correct

### "Login failed"
- Check browser console for errors
- Verify backend is deployed
- Check database migration ran

### "Wrong usage limits"
- Verify database migration completed
- Check service parameter is being sent
- Clear browser cache

### "Can't generate meta"
- Check OpenAI API key is set
- Verify you have usage remaining
- Check browser console for errors

## 📝 Test Checklist

- [ ] Backend health endpoint works
- [ ] Plans endpoint returns SEO AI Meta plans
- [ ] WordPress plugin login works
- [ ] Dashboard shows usage (0/10)
- [ ] Meta generation works in post editor
- [ ] Bulk generate works
- [ ] Usage tracking updates correctly
- [ ] Plans display correctly

---

**Once all checks pass, your SEO AI Meta Generator is fully operational! 🎉**

