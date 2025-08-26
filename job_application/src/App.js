import React, { useState } from "react";
import "./App.css";

export default function App() {
  const [formData, setFormData] = useState({});
  const [fileError, setFileError] = useState("");

  const handleChange = (e) => {
    const { name, value, type, checked, files } = e.target;
    if (type === "file") {
      const file = files[0];
      if (file && file.type !== "application/pdf") {
        setFileError("Only PDF files are allowed.");
        return;
      }
      if (file && file.size > 8192 * 1024) {
        setFileError("File size exceeds 8 MB.");
        return;
      }
      setFileError("");
      setFormData({ ...formData, [name]: file });
    } else {
      setFormData({ ...formData, [name]: type === "checkbox" ? checked : value });
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    alert("Form submitted! (Backend integration pending)");
  };

  return (
    <div className="app-container">
      <form className="app-card" onSubmit={handleSubmit}>
        <div className="two-col">
          <div className="form-section">
            <label>Firstname*</label>
            <input
              type="text"
              name="firstname"
              placeholder="John"
              required
              onChange={handleChange}
            />

            <label>Lastname*</label>
            <input
              type="text"
              name="lastname"
              placeholder="Doe"
              required
              onChange={handleChange}
            />

            <label>Email*</label>
            <input
              type="email"
              name="email"
              placeholder="john.doe@example.com"
              required
              onChange={handleChange}
            />

            <label>Country*</label>
            <input
              type="text"
              name="country"
              placeholder="Finland"
              required
              onChange={handleChange}
            />

            <label>Address Line 1*</label>
            <input
              type="text"
              name="address1"
              placeholder="Example Street 123"
              required
              onChange={handleChange}
            />

            <label>Address Line 2</label>
            <input
              type="text"
              name="address2"
              placeholder="Apartment 45B"
              onChange={handleChange}
            />

            <label>Zip*</label>
            <input
              type="text"
              name="zip"
              placeholder="33100"
              required
              onChange={handleChange}
            />

            <label>City*</label>
            <input
              type="text"
              name="city"
              placeholder="Tampere"
              required
              onChange={handleChange}
            />

            <label>Phone</label>
            <input
              type="tel"
              name="phone"
              placeholder="+358 3 123 4567"
              onChange={handleChange}
            />

            <label>Mobile*</label>
            <input
              type="tel"
              name="mobile"
              placeholder="+358 40 123 4567"
              required
              onChange={handleChange}
            />

            <label>WWW</label>
            <input
              type="url"
              name="www"
              placeholder="http://www.example.com"
              onChange={handleChange}
            />

            <label>Curriculum Vitae (PDF, max 8MB)*</label>
            <input type="file" name="cv" accept="application/pdf" required onChange={handleChange} />

            <label>Motivation Letter (PDF, max 8MB)</label>
            <input type="file" name="motivation" accept="application/pdf" onChange={handleChange} />

            <label>Job Application (PDF, max 8MB)</label>
            <input type="file" name="application" accept="application/pdf" onChange={handleChange} />

            {fileError && <p className="error-text">{fileError}</p>}

            <label>Additional Information*</label>
            <textarea
              name="additional"
              placeholder="I have 16 years of experience working in the IT/software development field..."
              required
              onChange={handleChange}
            />

            <label className="checkbox">
              <input type="checkbox" name="notRobot" required onChange={handleChange} /> I'm not a robot
            </label>
          </div>

          <div className="form-section">
            <label>Education*</label>
            <textarea
              name="education"
              placeholder="Degree Programme in Business Information Technology, TAMK, 2007-2011"
              required
              onChange={handleChange}
            />

            <label>Qualifications*</label>
            <textarea
              name="qualifications"
              placeholder="Red Hat Certified Salesperson, Red Hat University, 2011"
              required
              onChange={handleChange}
            />

            <label>Skills and Knowledge*</label>
            <textarea
              name="skills"
              placeholder="HTML, CSS, JavaScript, React, SQL, Java, PHP, Python..."
              required
              onChange={handleChange}
            />

            <label>Work Experience*</label>
            <textarea
              name="workexp"
              placeholder="i4ware Software, Product Owner, 2004 - present"
              required
              onChange={handleChange}
            />
          </div>
        </div>

        <div className="form-actions">
          <button type="submit" className="btn btn-primary">Submit</button>
          <button type="reset" className="btn btn-secondary">Reset</button>
        </div>
      </form>
    </div>
  );
}